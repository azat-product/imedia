<?php
/**
 * Профиль пользователя (layout)
 * @var $this Users
 * @var $user array данные пользователя
 * @var $is_owner boolean профиль просматривает его владелец
 */
?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content has-sidebar">

    <?php if (DEVICE_PHONE) {
      // Contacts Block (Desktop)
      echo $this->viewPHP($aData, 'profile.contacts');
    } ?>

    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= (!empty($titleh1) ? $titleh1 : _t('bbs', 'Объявления пользователя')) ?></h1>
    </div>

    <?= $content ?>

  </div><!-- /.l-mainLayout-content -->

  <?php if (DEVICE_DESKTOP_OR_TABLET) { ?>
    <!-- Sidebar -->
    <div class="l-mainLayout-sidebar">

      <?php
      // Contacts Block (Desktop)
      echo $this->viewPHP($aData, 'profile.contacts');
      ?>

      <?php if ($bannerRight = Banners::view('users_profile_right')): ?>
        <!-- Banners -->
        <div class="l-banner-v">
          <?= $bannerRight ?>
        </div>
      <?php endif; ?>

    </div><!-- /.l-mainLayout-sidebar -->
  <?php } ?>

</div><!-- /.l-mainLayout -->

<script type="text/javascript">
  <?php js::start(); ?>
  $(function () {
    var _process = false;
    $('.j-user-profile-c-toggler').on('click touchstart', function (e) {
      nothing(e);
      if (_process) return;
      var $link = $(this);
      bff.ajax(bff.ajaxURL('users', 'user-contacts'), {
          hash: app.csrf_token,
          ex: '<?= $user['user_id_ex'] ?>-<?= $user['user_id'] ?>'
        },
        function (data, errors) {
          if (data && data.success) {
            if (data.hasOwnProperty('phones')) {
              $('.j-user-profile-c-phones').html(data['phones']);
            }
            if (data.hasOwnProperty('contacts')) {
              for (var c in data.contacts) {
                $('.j-user-profile-c-' + c).html(data.contacts[c]);
              }
            }
            $link.remove();
          } else {
            app.alert.error(errors);
          }
        }, function (p) {
          _process = p;
        }
      );
    });
  });
  <?php js::stop(); ?>
</script>