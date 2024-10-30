<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$cptmr_settings = get_option('cptmr_settings');
?>

<style>
  .post-type{
    padding: 20px;
    margin-top: 20px;
    background-color: white;
    border: 1px solid #d3d3d3; 
  }
  .route-wrapper{
    display: block;
    margin: 10px 0px;
  }
  .route-wrapper:not(:first-child){
    border-top: 1px solid #d3d3d3; 
    padding-top: 10px;
  }

  .route-wrapper span.postcount{
    margin-left: 10px;
  }

  .route{
    display: inline-block;
  }
  .route label{
    padding: 10px;
    text-transform: capitalize;
    font-weight: bold;
  }
  .route:not(:first-child) label{
    margin-left: 10px;
    border-left: 1px solid #d3d3d3; 

  }
  .removeRoute{
    margin-left: 10px;
  }
  .route-wrapper.unsaved{
    padding: 10px;
    background-color: #c9ff93;
    border: 0;
  }
  .route-wrapper.unsaved:after{
    margin-left: 10px;
    font-size: 10px;
    content: '<?= __('New route. Remember to press "Save Routes" before leaving.', 'cptmr'); ?>';
  }
</style>

<div class="wrap">
  <h2><?php _e('Custom Post Type Multiroutes Settings', 'cptmr'); ?></h2>
  <p><?=__('Setup all the routes and translations for your custom post types (both single and archive pages).', 'cptmr');?></p>
  <?php if (isset($_GET['msg'])) : ?>
    <div id="message" class="updated below-h2">
      <?php if ($_GET['msg'] == 'update') : ?>
        <p><?php _e('Routes saved. Remember to <a href="options-permalink.php">update your permalinks.</a>','cptmr'); ?></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <?php if (function_exists('wp_nonce_field')) wp_nonce_field('nonce_cptmr'); ?>
    <?php if(empty($post_types)): ?>
       <p> <?= __('It seems you have not defined any custom post types yet.','cptmr'); ?></p>
    <?php else: ?>
      <? foreach ($post_types as $post_type): ?>
        <div class="post-type" data-cpt="<?=$post_type->name;?>">
        <h3>
          <?=ucfirst($post_type->name);?>
          <button class="addRoute"><?= __('Add Route', 'cptmr'); ?></button>
        </h3>
        <p>
          <?php echo '<input type="checkbox" name="cptmr_settings[rewrite_archive][' . $post_type->name . ']" value="' . $post_type->name . '" ' . ((isset($cptmr_settings['rewrite_archive']) && in_array($post_type->name, $cptmr_settings['rewrite_archive'])) ? 'checked' : '') . '>' . __('Rewrite this post type archive to these routes.', 'cptmr'); ?>
        </p>
        <div class="routes-box">
        <?
        if(isset($cptmr_settings['cpt'][$post_type->name])){
          foreach ($cptmr_settings['cpt'][$post_type->name] as $routeKey => $cptRoutes) {

            // Post count for this route
            $args = array(
              'post_type' => $post_type->name,
              'meta_query' => array(
                array(
                  'key' => '_cptmr_route',
                  'value' => $routeKey
                )
              )
            );
            $postCount = count( get_posts( $args ) );

            echo '<div class="route-wrapper" data-removemsg="' . sprintf(__('Are you sure to remove this route? Route metadata will be deleted from %s posts.', 'cptmr'), $postCount) . '">';
              foreach ($this->langs as $langCode => $lang) {
                echo '<div class="route">';
                  echo '<label for="cptmr_settings[cpt][' . $post_type->name . '][' . $routeKey . '][' . $langCode . ']">' . $langCode . '</label>';
                  echo '<input type="text" name="cptmr_settings[cpt][' . $post_type->name . '][' . $routeKey . '][' . $langCode . ']" id="" value="' . ( isset($cptRoutes[$langCode]) ? $cptRoutes[$langCode] : ''  ) . '">';
                echo '</div>';
              }
              echo '<button class="removeRoute">' . __('Remove Route', 'cptmr') . '</button>';
              echo '<span class="postcount">';
                printf(__('%s posts using this route.', 'cptmr'), $postCount);
              echo '</span>';
            echo '</div>';
          }
        }

        ?>
          </div> <!-- end routes-box -->
        </div> <!-- end post-type -->
      <? endforeach; ?>

      <p class="submit">
          <input type="submit" class="button-primary" name="cptmr_submit" value="<?php _e('Save Routes', 'cptmr'); ?>">
      </p>
    </form>
  <?php endif; ?>
</div>

<script>
    (function ($) {

      const langs = <?php echo json_encode($this->langs); ?>;
      const ItemWrapper = `<div class="route-wrapper unsaved"></div>`;
      const Item = ({ lang, cpt, num }) => `
        <div class="route">
          <label for="cptmr_settings[cpt][${cpt}][${num}][${lang}]">${lang}</label>
          <input type="text" name="cptmr_settings[cpt][${cpt}][${num}][${lang}]">
        </div>
      `;
      const removeButton = `<button class="removeRoute"><?=__('Remove Route', 'cptmr');?></button>`;

      $('button.addRoute').click(function(e) {
        e.preventDefault();
        var postType = $(this).closest('.post-type').data('cpt');
        var $thisEl = $(this).closest('.post-type').find('.routes-box');
        var routeId = uuidv4();

        $thisEl.append(ItemWrapper);

        Object.keys(langs).forEach(function(key) {
          $thisEl.find('div.route-wrapper:last').append([
            { lang: key, cpt: postType, num: routeId }
          ].map(Item).join(''));
        });

        $thisEl.find('div.route-wrapper:last').append(removeButton);
      });

      $('body').on('click', 'button.removeRoute', function(e) {
        e.preventDefault();
        var $routeWrapper = $(this).closest('.route-wrapper');
        if (window.confirm($routeWrapper.data('removemsg'))) {
          $(this).closest('.route-wrapper').remove();
        }
      });

      function uuidv4() {
        return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
          (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
        );
}


    })(jQuery)
</script>
