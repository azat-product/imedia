<?php

/**
 * Класс синхронизации с форумом
 * @version 0.3
 * @modified 10.mar.2015
 */

class UsersForumBase extends Component
{
    protected $type = '';
    protected $logging = false;

    public function init()
    {
        parent::init();

        $this->type = config::sys('forum.type');
        switch ($this->type)
        {
            case 'xenforo':
            {
                $path = config::sys('forum.xenforo.path');
                if (empty($path) || ! file_exists($path)) {
                    $this->log('forum.init = check config.sys "forum.xenforo.path"');
                    $this->type = '';
                }
                include_once modification(PATH_CORE.'session'.DS.'xenforo'.DS.'bridge.php');
                if (!class_exists('bffXenforoBridge')) {
                    $this->log('forum.init = xenforo bridge class does not exists "bffXenforoBridge"');
                    $this->type = '';
                }
            } break;
        }
    }

    protected function dbPrefix()
    {
        return config::sys('forum.db.prefix');
    }

    /**
     * Выполняем подключение к базе форума
     * @return \bff\db\Database | bool
     */
    protected function dbConnection()
    {
        static $db;
        if (!isset($db)) {
            try {
                $db = bff::DI('database_factory');
                $db->connectionConfig('forum.db');
                $db->connect();
            } catch (\Exception $e) {
                bff::log($e->getMessage());
                $db = false;

                return false;
            }
        }

        return $db;
    }

    /**
     * Список последних постов форума
     * @param integer $nLimit кол-во постов
     * @param integer $nAvatarSize размер аватара пользователя UsersAvatar::sz_
     * @return array
     */
    public function getLastIndexPosts($nLimit = 8, $nAvatarSize = UsersAvatar::szSmall)
    {
        switch ($this->type)
        {
            case 'phpbb3':
            {
                $dbForum = $this->dbConnection();
                if (empty($dbForum)) break;

                $aData = $dbForum->select('
                    SELECT U.username, P.post_time, P.post_subject, P.post_id, P.topic_id, P.forum_id
                    FROM '.$this->dbPrefix().'posts P, '.$this->dbPrefix().'users U
                    WHERE P.poster_id = U.user_id AND P.post_approved = 1
                    ORDER BY P.post_time DESC
                    LIMIT '.$nLimit);
                if (empty($aData)) break;
                $aUsers = array();
                foreach ($aData as $v) {
                    if ( ! in_array($v['username'], $aUsers)) {
                        $aUsers[] = $v['username'];
                    }
                }
                $aUsers = $this->db->select_key('
                    SELECT user_id, login, name, surname, avatar, sex
                    FROM '.TABLE_USERS.'
                    WHERE '.$this->db->prepareIN('login', $aUsers), 'login');
                $url = Users::url('forum').'viewtopic.php?';
                foreach ($aData as $k => $v) {
                    if (isset($aUsers[ $v['username'] ])) {
                        $v += $aUsers[ $v['username'] ];
                        $v['link'] = $url.'f='.$v['forum_id'].'&t='.$v['topic_id'].'#p'.$v['post_id'];
                        $v['avatar'] = UsersAvatar::url($v['user_id'], $v['avatar'], $nAvatarSize, $v['sex']);
                        $v['publicated'] = tpl::date_publicated(date('Y-m-d H:i:s', $v['post_time']));
                        $aData[$k] = $v;
                    } else {
                        unset($aData[$k]);
                    }
                }

                return $aData;
            }
            break;
            case 'vbulletin5':
            {
                return array();
            }
            break;
            case 'xenforo':
            {
                $aResult = array();

                $aData = $this->xenforo()->getThreads(array(
                    'not_discussion_type' => 'redirect',
                    'deleted' => false,
                    'moderated' => false,
                    'find_new' => true,
                ), array(
                    'order'          => 'last_post_date',
                    'orderDirection' => 'desc',
                    'limit'          => $nLimit,
                ));
                if (empty($aData)) break;

                $aUsers = array();
                foreach($aData as $v){
                    $aUsers[] = $v['user_id'];
                }
                $aUsers = array_unique($aUsers);
                $aUsers = $this->xenforo()->getUsersByIds($aUsers);

                $nAuthUserId = User::id();
                $sForumBaseUrl = config::sys('forum.baseurl');
                $nPPage = XenForo_Application::get('options')->messagesPerPage;
                foreach ($aData as $v) {
                    $u['name'] = $v['username'];
                    $u['surname'] = '';
                    $u['link'] = XenForo_Link::buildPublicLink('canonical:threads', $v);
                    if($nAuthUserId){
                        $u['link'] .= 'unread';
                    }elseif($nPPage){
                        $nPageid =  floor($v['reply_count'] / $nPPage) + 1;
                        $aParam = array('page' => $nPageid);
                        $action = XenForo_Link::getPageNumberAsAction('', $aParam);
                        if( ! empty($action)){
                            $u['link'] .= $action;
                        }
                    }
                    $u['publicated'] = tpl::date_publicated(date('Y-m-d H:i:s', $v['last_post_date']));
                    $u['post_subject'] = $v['title'];
                    $user = isset($aUsers[$v['user_id']]) ? $aUsers[$v['user_id']] : $this->xenforo()->getUser($v['user_id']);
                    $u['avatar'] = $sForumBaseUrl.XenForo_Template_Helper_Core::callHelper('avatar', array($user, 's'));
                    $aResult[] = $u;
                }
                return $aResult;
            }
            break;
        }
        return array();
    }

    /**
     * Обрабатываем событие смены логина пользователем
     * @param integer $userID ID пользователя в базе сайта
     * @param string $loginNew новый логин
     * @param string $loginOld старый логин
     * @apram mixed ID текущей сессии пользователя ($userID)
     * @return bool true - логин успешно изменен, false - ошибка смены логина
     */
    public function onUserLoginChanged($userID, $loginNew, $loginOld, $mSessionID = null)
    {
        $isSuccess = false;
        $response = '';
        switch ($this->type)
        {
            case 'phpbb3':
            {
                $dbForum = $this->dbConnection();
                if (empty($dbForum)) {
                    break;
                }

                # заменяем логин(username) на новый:
                $response = $dbForum->exec('UPDATE ' . $this->dbPrefix() . 'users
                         SET username = :new, username_clean = :new
                         WHERE username = :old',
                    array(':new' => $loginNew, ':old' => $loginOld)
                );

                $isSuccess = !empty($response);
            }
            break;
            case 'vbulletin5':
            {
                $sVbulletin5Url = config::sys('forum.vbulletin5.baseurl');
                if ( ! $sVbulletin5Url) break;

                $sVbulletin5Url .= '/bff/rename';
                $aData = array(
                    'hash'      => md5($userID.$loginNew.config::sys('forum.vbulletin5.salt')),
                    'userid'    => $userID,
                    'username'  => $loginNew,
                );
                $ch = curl_init($sVbulletin5Url);
                curl_setopt($ch, CURLOPT_FAILONERROR, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aData));
                $resp = curl_exec($ch);
                curl_close($ch);
                if (empty($resp)) break;

                $mAnswer = json_decode($resp);
                if( ! empty($mAnswer->success)) {
                    $isSuccess = true;
                }
            }
            break;
            case 'xenforo':
            {
                $response = $this->xenforo()->changeUsername($loginNew, $loginOld);

                $isSuccess = !empty($response);
            }
            break;
            default:
            {
                $isSuccess = true;
            }
            break;
        }

        # обновляем данные в сессии (для того, чтобы их подхватил форум)
        if (!bff::adminPanel()) {
            # в текущей сессии
            $this->security->updateUserInfo(array('login' => $loginNew));
        } else {
            # в сессии пользователя $userID
            $this->security->impersonalizeSession($mSessionID, array('login' => $loginNew), false, false);
        }

        $this->log('forum.login.change = '.($isSuccess ? 'OK' : 'FAIL'), array('id'=>$userID, 'login.new'=>$loginNew, 'login.old'=>$loginOld, 'response'=>$response));

        return $isSuccess;
    }

    public function onUserRegister($userID, $userLogin, $userEmail, $userPassword, array $aExtra = array())
    {
        $response = '';
        switch ($this->type)
        {
            case 'xenforo':
            {
                $response = $this->xenforo()->addUser($userEmail, $userLogin, $userPassword, $aExtra);

                $isSuccess = !empty($response);
            }
            break;
            default:
                $isSuccess = true;
            break;
        }

        $this->log('forum.register = '.($isSuccess ? 'OK' : 'FAIL'), array('id'=>$userID, 'login'=>$userLogin, 'email'=>$userEmail, 'response'=>$response));

        return $isSuccess;
    }

    public function onUserLogin($userID, $userLogin, $userEmail)
    {
        $response = '';
        switch ($this->type)
        {
            case 'xenforo':
            {
                $response = $this->xenforo()->loginByUsername($userLogin);

                $isSuccess = !empty($response);
            }
            break;
            default:
                $isSuccess = true;
            break;
        }

        $this->log('forum.login = '.($isSuccess ? 'OK' : 'FAIL'), array('id'=>$userID, 'login'=>$userLogin, 'email'=>$userEmail, 'response'=>$response));

        return $isSuccess;
    }

    public function onUserLogout()
    {
        switch ($this->type)
        {
            case 'xenforo':
            {
                $res = $this->xenforo()->logout();

                $isSuccess = !empty($res);
            }
            break;
            default:
                $isSuccess = true;
            break;
        }

        $this->log('forum.logout = '.($isSuccess ? 'OK' : 'FAIL'));

        return $isSuccess;
    }

    /**
     * Синхронизация пользователей
     * @return bool|int
     */
    public function syncUsers()
    {
        set_time_limit(0);

        switch ($this->type)
        {
            case 'xenforo':
            {
                $oXF = $this->xenforo();

                $aUsersXF = array();
                $aData = XenForo_Model::create('XenForo_Model_User')->getUsers(array());
                foreach ($aData as $v) {
                    $aUsersXF[$v['username']] = array('user_id' => $v['user_id']);
                }
                unset($aData);

                $aUsers = $this->db->select('SELECT user_id, login, email, password
                    FROM '.TABLE_USERS.'
                    WHERE activated = 1');

                $nCnt = 0;
                foreach ($aUsers as $v) {
                    if ( ! isset($aUsersXF[$v['login']])) {
                        $oXF->addUser($v['email'], $v['login'], $v['password']);
                        $nCnt++;
                    }
                }
                return $nCnt;
            }
        }
        return false;
    }

    /**
     * @return bffXenforoBridge
     */
    protected function xenforo()
    {
        static $i;
        if (!isset($i)) {
            $i = new bffXenforoBridge(config::sys('forum.xenforo.path', PATH_PUBLIC.'xenforo'));
        }
        return $i;
    }

    protected function log($message, $params = null)
    {
        if ($this->logging) {
            bff::log($message);
            if (!is_null($params)) {
                bff::log($params);
            }
        }
    }

}