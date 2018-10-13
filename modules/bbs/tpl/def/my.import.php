<?php
/**
 * Кабинет пользователя: Импорт объявлений - layout
 * @var $this BBS
 * @var $list array список
 * @var $list_total integer всего записей в списке
 * @var $pgn string постраничная навигация (HTML)
 * @var $pgn_pp array варианты кол-ва на страницу
 */
tpl::includeJS('bbs.my.import', false, 6);
?>
<h2><?= _t('bbs.import', 'Импорт объявлений') ?></h2>

<form action="" id="j-my-import-form" class="form-horizontal" enctype="multipart/form-data">
    <input type="hidden" name="sAction" value="import" />
    <div class="well">
        <div class="control-group control-group__100">
            <label class="control-label"><?= _t('bbs.import', 'Категория') ?></label>
            <div class="controls">
                <input type="hidden" name="cat_id" class="j-cat-value" value="0" />
                <div class="i-formpage__catselect rel">
                    <div class="i-formpage__catselect__done j-cat-select-link-selected hide">
                        <img class="abs j-icon" alt="" src="" />
                        <div class="i-formpage__catselect__done_cat">
                            <a href="javascript:void(0);" class="j-cat-select-link j-title"></a>
                        </div>
                    </div>
                    <div class="i-formpage__catselect__close j-cat-select-link-empty">
                        <a href="javascript:void(0);" class="ajax ajax-ico j-cat-select-link"><span><?= _t('bbs.import', 'Выберите категорию') ?></span> <i class="fa fa-chevron-down"></i></a>
                    </div>
                    <div class="i-formpage__catselect__popup dropdown-block box-shadow abs hide j-cat-select-popup">
                        <div class="i-formpage__catselect__popup__content">
                            <? if( DEVICE_DESKTOP_OR_TABLET ): ?>
                            <div class="i-formpage__catselect__popup__mainlist j-cat-select-step1-desktop">
                                <?= $this->catsList('form', bff::DEVICE_DESKTOP, 0); ?>
                            </div>
                            <div class="i-formpage__catselect__popup__sublist j-cat-select-step2-desktop hide"></div>
                            <? endif; ?>
                            <? if( DEVICE_PHONE ): ?>
                            <div class="i-formpage__catselect__popup__mainlist j-cat-select-step1-phone">
                                <?= $this->catsList('form', bff::DEVICE_PHONE, 0); ?>
                            </div>
                            <div class="i-formpage__catselect__popup__sublist j-cat-select-step2-phone hide"></div>
                            <? endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label"><?= _t('bbs.import', 'Статус объявлений') ?><span class="required-mark">*</span></label>
            <div class="controls j-cat-types">
                <label class="radio inline"><input name="status" value="<?= BBS::STATUS_PUBLICATED ?>" type="radio" class="j-cat-type j-required" checked="checked"> <?= _t('ibbs.import', 'Опубликованы') ?></label>
                <label class="radio inline"><input name="status" value="<?= BBS::STATUS_PUBLICATED_OUT ?>" type="radio" class="j-cat-type j-required"> <?= _t('bbs.import', 'Сняты с публикации') ?></label>
            </div>
        </div>
        <? if(BBS::formPublicationPeriod()): $publicationPeriodOpts = $this->publicationPeriodOptions($daysDefault); ?>
            <div class="control-group j-publicate-period">
                <label class="control-label"><?= _t('bbs.import', 'Период публикации') ?><span class="required-mark">*</span></label>
                <div class="controls j-cat-types">
                    <select name="publicate_period" class="input-medium"><?= HTML::selectOptions($publicationPeriodOpts, $daysDefault, false, 'days', 't') ?></select>
                </div>
            </div>
        <? endif; ?>
        <? if(BBS::importUrlEnabled()): ?>
        <div class="control-group">
            <label class="control-label"><?= _t('bbs.import', 'Источник') ?><span class="required-mark">*</span></label>
            <div class="controls j-import-types">
                <label class="radio inline"><input name="type" value="<?= BBSItemsImport::TYPE_FILE ?>" type="radio" class="j-import-type j-required" checked="checked" /> <?= _t('bbs.import', 'Файл') ?></label>
                <label class="radio inline"><input name="type" value="<?= BBSItemsImport::TYPE_URL ?>" type="radio" class="j-import-type j-required" /> <?= _t('bbs.import', 'Ссылка на файл') ?></label>
            </div>
        </div>
        <? endif; ?>
        <div class="control-group j-file-import">
           <label class="control-label"><?= _t('', 'Файл') ?><span class="required-mark">*</span></label>
           <div class="controls">
               <div class="v-descr_contact__form_file attach-file j-attach-block" style="padding-top:5px;">
                    <div class="upload-btn j-upload" style="width: 300px;">
                        <span class="upload-mask">
                            <input type="file" name="file" class="j-upload-file" />
                        </span>
                        <a href="javascript:void(0);" onclick="return false;" class="ajax"><?= _t('', 'Выбрать файл, до 10мб') ?></a>
                    </div>
                    <div class="j-cancel hide">
                        <span class="j-cancel-filename"></span>
                        <a href="javascript:void(0);" class="ajax pseudo-link-ajax ajax-ico j-cancel-link"><i class="fa fa-times"></i> <?= _t('', 'отмена') ?></a>
                    </div>
                </div>
            </div>
        </div>
        <? if(BBS::importUrlEnabled()): ?>
        <div class="control-group j-url-import hide">
            <label class="control-label"><?= _t('', 'URL') ?><span class="required-mark">*</span></label>
            <div class="controls">
                <div class="input-prepend">
                    <span class="add-on"><i class="fa fa-globe"></i></span>
                    <input type="text" name="url" value=""  class="input-xxlarge j-url-input" placeholder="<?= _te('bbs.import', 'Укажите URL-путь к файлу импорта, например http://site.com/import.xml') ?>" maxlength="1500" />
                </div>
            </div>
        </div>
        <div class="control-group j-url-import hide">
            <label class="control-label"><?= _t('', 'Период обработки') ?><span class="required-mark">*</span></label>
            <div class="controls">
                <div class="input-prepend">
                    <select id="j-url-import-period" name="period" class="input-small"><?= BBSItemsImport::importPeriodOptions(true) ?></select>
                </div>
            </div>
        </div>
        <? endif; ?>
        <div class="control-group">
            <div class="controls">
                <input type="submit" class="btn btn-success j-submit" value="<?= _te('bbs.import', 'Импортировать'); ?>" data-loading-text="<?= _te('','Подождите...'); ?>" disabled="disabled" />
                <input type="button" class="btn btn-default j-template" data-ext="xml" value="<?= BBS::importCsvFrontendEnabled() ? _te('bbs.import', 'Скачать шаблон xml') : _te('bbs.import', 'Скачать шаблон'); ?>" disabled="disabled" />
                <? if (BBS::importCsvFrontendEnabled()): ?>
                    <input type="button" class="btn btn-default j-template" data-ext="csv" value="<?= _te('bbs.import', 'Скачать шаблон csv'); ?>" disabled="disabled" />
                <? endif; ?>
                <?= bff::filter('bbs.import.cabinet.buttons', ''); ?>
            </div>
        </div>
    </div>
</form>

<?= $periodic ?>
<? if ( $list_total > 0 ) { ?>
<div class="u-cabinet__sub-navigation">
    <div class="u-cabinet__sub-navigation_desktop u-cabinet__sub-navigation_bill hidden-phone">
        <div class="pull-left">
             <h3><?= _t('bbs.import','История импорта'); ?></h3>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="u-cabinet__sub-navigation_mobile u-cabinet__sub-navigation_bill visible-phone">
        <div class="pull-left"><h3><?= _t('bbs.import','История импорта'); ?></h3></div>
        <div class="clearfix"></div>
    </div>
</div>
<form action="" id="j-my-import-history-form">
<input type="hidden" name="page" value="0">
<input type="hidden" name="pp" value="15" id="j-my-import-history-pp-value">

<div class="u-bill__list">
    <table>
        <tbody><tr>
            <th style="padding-left: 10px; width: 170px;" class="align-left"><?= _t('bbs.import','Категория'); ?></th>
            <th style="width: 110px;"><?= _t('bbs.import','Объявлений'); ?></th>
            <th style="width: 110px;"><?= _t('bbs.import','Обработано'); ?></th>
            <th style="padding-left: 10px;" class="align-left"><?= _t('bbs.import','Комментарий'); ?></th>
            <th style="width: 100px;"><?= _t('bbs.import','Статус'); ?></th>
            <th style="width: 90px;"><?= _t('bbs.import','Действие'); ?></th>
        </tr>
        </tbody>
        <tbody id="j-my-import-history-list">
            <?= $list ?>
        </tbody>
    </table>
</div>

<? # Постраничная навигация ?>
<? if ( $list_total > 15 ) { ?>
<div class="u-cabinet__pagination">
    <div class="pull-left" id="j-my-import-history-pgn">
        <?= $pgn ?>
    </div>
    <ul id="j-my-import-history-pp" class="u-cabinet__list__pagination__howmany nav nav-pills pull-right hidden-phone">
        <li class="dropdown">
            <a class="dropdown-toggle j-pp-dropdown" data-toggle="dropdown" href="javascript:void(0);">
                <span class="j-pp-title"><?= $pgn_pp[$f['pp']]['t'] ?></span>
                <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu">
                <? foreach($pgn_pp as $k=>$v): ?>
                    <li><a href="javascript:void(0);" class="<? if($k == $f['pp']) { ?>active <? } ?>j-pp-option" data-value="<?= $k ?>"><?= $v['t'] ?></a></li>
                <? endforeach; ?>
            </ul>
        </li>
    </ul>
    <div class="clearfix"></div>
</div>
<? } } ?>
</form>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jBBSMyImport.init(<?= func::php2js(array(
            # category
            'catsRootID' => BBS::CATS_ROOTID,
            'catsMain'   => $this->catsList('form', 'init'),
            'typeFile'   => BBSItemsImport::TYPE_FILE,
            'typeUrl'    => BBSItemsImport::TYPE_URL,
            'statusPublicated' => BBS::STATUS_PUBLICATED,
            # lang
            'lang' => array(
                'wrongFormat'   => _t('bbs.import', 'Разрешено импортировать только файлы в формате xml'),
                'success'       => _t('bbs.import', 'Импортирование объявлений было успешно инициировано'),
                'delete_confirm'=> _t('bbs.import', 'Вы действительно хотите удалить импорт?'),
            ),
        )) ?>);
        <? if( ! empty($errors)): if(is_array($errors)){ $errors = join('<br />', $errors); } ?>app.alert.error('<?= $errors ?>');
        <? endif; ?>
    });
<? js::stop(); ?>
</script>