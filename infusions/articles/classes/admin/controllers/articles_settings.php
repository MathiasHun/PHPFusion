<?php
/*-------------------------------------------------------+
| PHPFusion Content Management System
| Copyright (C) PHP Fusion Inc
| https://phpfusion.com/
+--------------------------------------------------------+
| Filename: article_settings.php
| Author: Core Development Team (coredevs@phpfusion.com)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
namespace PHPFusion\Articles;

class ArticlesSettingsAdmin extends ArticlesAdminModel {
    private static $instance = NULL;

    public static function getInstance() {
        if (self::$instance == NULL) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public function displayArticlesAdmin() {
        pageAccess("A");
        $locale = self::get_articleAdminLocale();
        $article_settings = self::get_article_settings();

        // Save
        if (isset($_POST['savesettings'])) {
            $inputArray = [
                'article_pagination'            => form_sanitizer($_POST['article_pagination'], 15, 'article_pagination'),
                'article_allow_submission'      => form_sanitizer($_POST['article_allow_submission'], 0, 'article_allow_submission'),
                'article_extended_required'     => form_sanitizer($_POST['article_extended_required'], 0, 'article_extended_required'),
                'article_submission_visibility' => form_sanitizer($_POST['article_submission_visibility'], 0, 'article_submission_visibility')
            ];

            // Update
            if (\defender::safe()) {
                foreach ($inputArray as $settings_name => $settings_value) {
                    $inputSettings = [
                        'settings_name' => $settings_name, 'settings_value' => $settings_value, 'settings_inf' => 'articles',
                    ];
                    dbquery_insert(DB_SETTINGS_INF, $inputSettings, 'update', ['primary_key' => 'settings_name']);
                }
                addNotice('success', $locale['900']);
                redirect(FUSION_REQUEST);
            } else {
                addNotice('danger', $locale['901']);
                $article_settings = $inputArray;
            }
        }

        echo "<div class='well m-t-10'>".$locale['article_0400']."</div>";

        echo openform('settingsform', 'post', FUSION_REQUEST, ['class' => 'spacer-sm']);
        echo form_text('article_pagination', $locale['article_0401'], $article_settings['article_pagination'], [
            'inline'      => TRUE,
            'max_length'  => 4,
            'inner_width' => '250px',
            'width'       => '150px',
            'type'        => 'number'
        ]);
        echo form_select('article_allow_submission', $locale['article_0007'], $article_settings['article_allow_submission'], [
            'inline'  => TRUE,
            'options' => [$locale['disable'], $locale['enable']]
        ]);
        echo form_select('article_submission_visibility[]', $locale['submissions_visibility'], $article_settings['article_submission_visibility'], [
            'inline'   => TRUE,
            'options'  => fusion_get_groups(),
            'multiple' => TRUE,
        ]);
        echo form_select('article_extended_required', $locale['article_0403'], $article_settings['article_extended_required'], [
            'inline'  => TRUE,
            'options' => [$locale['disable'], $locale['enable']]
        ]);
        echo form_button('savesettings', $locale['750'], $locale['750'], ['class' => 'btn-success', 'icon' => 'fa fa-fw fa-hdd-o']);
        echo closeform();
    }
}
