<?php
/*-------------------------------------------------------+
| PHPFusion Content Management System
| Copyright (C) PHP Fusion Inc
| https://phpfusion.com/
+--------------------------------------------------------+
| Filename: Atom/Admin.php
| Author: Frederick MC Chan (Chan)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
namespace PHPFusion\Atom;

use PHPFusion\BreadCrumbs;

/**
 * Administration Page for Theme Settings
 * Class Admin
 *
 * @package PHPFusion\Atom
 */
class Admin {

    private static $locale = [];

    public function __construct() {
        $aidlink = fusion_get_aidlink();
        self::$locale = fusion_get_locale();
        $_GET['action'] = isset($_GET['action']) && $_GET['action'] ? $_GET['action'] : '';
        $_GET['status'] = isset($_GET['status']) && $_GET['status'] ? $_GET['status'] : '';
        BreadCrumbs::getInstance()->addBreadCrumb(['link' => ADMIN."theme.php".$aidlink, 'title' => self::$locale['theme_1000']]);
    }

    /**
     * Check if a theme widget file exist
     *
     * @param $theme_name
     *
     * @return bool
     */
    static function theme_widget_exists($theme_name) {
        return (is_dir(THEMES.$theme_name) && file_exists(THEMES.$theme_name."/widget.php"));
    }

    /**
     * The Theme Editor - Manage UI
     *
     * @param $theme_name
     */
    public static function display_theme_editor($theme_name) {
        $locale = fusion_get_locale();
        $aidlink = fusion_get_aidlink();
        // sanitize theme exist
        $theme_name = self::verify_theme($theme_name) ? $theme_name : "";
        if (!$theme_name) {
            redirect(clean_request("", ["aid"], TRUE));
        }

        BreadCrumbs::getInstance()->addBreadCrumb(['link' => FUSION_REQUEST, 'title' => $locale['theme_1018']]);
        // go with tabs
        $tab['title'] = [$locale['theme_1022'], $locale['theme_1023'], $locale['theme_1024']];
        $tab['id'] = ["dashboard", "widgets", "css"];
        $tab['icon'] = ["fa fa-edit fa-fw", "fa fa-cube fa-fw", "fa fa-css3 fa-fw"];
        if (isset($_GET['action'])) {
            $tab['title'][] = $locale['theme_1029'];
            $tab['id'][] = "close";
            $tab['icon'][] = "fa fa-close fa-fw";
        }
        if (isset($_POST['close_theme'])) {
            redirect(FUSION_SELF.$aidlink);
        }
        $_GET['section'] = isset($_GET['section']) && in_array($_GET['section'], $tab['id']) ? $_GET['section'] : "dashboard";
        $tab_active = $_GET['section'];
        $atom = new Atom();
        $atom->target_folder = $theme_name;
        $atom->theme_name = $theme_name;
        echo opentab($tab, $tab_active, "theme_admin", TRUE, 'nav-tabs');
        // now include the thing as necessary
        switch ($_GET['section']) {
            case "dashboard":
                /**
                 * Delete preset
                 */
                if (isset($_GET['delete_preset']) && isnum($_GET['delete_preset'])) {
                    if (empty($_GET['theme'])) {
                        redirect(FUSION_SELF.$aidlink);
                    }

                    $theme_name = stripinput($_GET['theme']);
                    $file = dbarray(dbquery("SELECT theme_file FROM ".DB_THEME." WHERE theme_name='".$theme_name."' AND theme_id='".intval($_GET['delete_preset'])."'"));
                    if (file_exists(THEMES.$file['theme_file'])) {
                        unlink(THEMES.$file['theme_file']);
                    }

                    dbquery("DELETE FROM ".DB_THEME." WHERE theme_id='".intval($_GET['delete_preset'])."'");
                    addNotice('success', $locale['theme_success_002']);
                    redirect(clean_request("", ["section", "aid", "action", "theme"], TRUE));
                }
                /**
                 * Set active presets
                 */
                if (isset($_POST['load_preset']) && isnum($_POST['load_preset'])) {
                    $result = dbquery("select theme_id FROM ".DB_THEME." WHERE theme_active='1'");
                    if (dbrows($result) > 0) {
                        $data = dbarray($result);
                        $data = [
                            "theme_id"     => $data['theme_id'],
                            "theme_active" => 0,
                        ];
                        dbquery_insert(DB_THEME, $data, "update");
                    }
                    $data = [
                        "theme_id"     => $_POST['load_preset'],
                        "theme_active" => 1,
                    ];
                    dbquery_insert(DB_THEME, $data, "update");
                    redirect(clean_request("", ["section", "aid", "action", "theme"], TRUE));
                }
                $atom->display_theme_overview();
                break;
            case "widgets":
                $atom->display_theme_widgets();
                break;
            case "css":
                echo '<div class="alert alert-danger">'.$locale['deprecated_section'].'</div>';
                $atom->theme_editor();
                break;
            case "close":
                redirect(FUSION_SELF.$aidlink);
                break;
            default:
                break;
        }
        echo closetab();
    }

    /**
     * Verify theme exist
     *
     * @param $theme_name
     *
     * @return bool
     */
    public static function verify_theme($theme_name) {
        return (is_dir(THEMES.$theme_name) && file_exists(THEMES.$theme_name."/theme.php") && file_exists(THEMES.$theme_name."/styles.css") && fusion_get_settings('theme') == $theme_name);
    }

    public static function display_theme_list() {
        $locale = fusion_get_locale();
        $aidlink = fusion_get_aidlink();
        $settings = fusion_get_settings();
        if (isset($_GET['action']) && $_GET['action'] == "set_active" && isset($_GET['theme']) && $_GET['theme'] !== "") {
            $theme_name = form_sanitizer($_GET['theme']);
            if (self::theme_installable($theme_name)) {
                $result = dbquery("UPDATE ".DB_SETTINGS." SET settings_value='".$theme_name."' WHERE settings_name='theme'");
                if ($result) {
                    redirect(FUSION_SELF.$aidlink.'&section=list');
                }
            }
        }
        $data = [];
        $_dir = makefilelist(THEMES, ".|..|templates|admin_themes", TRUE, "folders");
        foreach ($_dir as $folder) {
            $theme_dbfile = 'theme_db.php';
            $status = $settings['theme'] == $folder ? 1 : 0;
            $themefolder = THEMES.$folder.'/';

            if (file_exists($themefolder.$theme_dbfile)) {
                // 9.00 compatible theme.
                include_once $themefolder.$theme_dbfile;
                $data[$status][$folder] = [
                    'readme'      => !empty($theme_readme) && file_exists($themefolder.$theme_readme) ? $themefolder.$theme_readme : '',
                    'screenshot'  => isset($theme_screenshot) && file_exists($themefolder.$theme_screenshot) ? $themefolder.$theme_screenshot : IMAGES.'imagenotfound.jpg',
                    'title'       => isset($theme_title) ? $theme_title : '',
                    'web'         => isset($theme_web) ? $theme_web : '',
                    'author'      => isset($theme_author) ? $theme_author : '',
                    'license'     => isset($theme_license) ? $theme_license : '',
                    'version'     => isset($theme_version) ? $theme_version : '',
                    'description' => isset($theme_description) ? $theme_description : '',
                    'widgets'     => file_exists($themefolder.'widget.php')
                ];
            } else {
                // older legacy theme.
                if (file_exists($themefolder.'theme.php')) {
                    $theme_screenshot = file_exists($themefolder.'screenshot.png') ? $themefolder.'screenshot.png' : $themefolder.'screenshot.jpg';
                    $data[$status][$folder] = [
                        'readme'      => '',
                        'title'       => $folder,
                        'screenshot'  => file_exists($theme_screenshot) ? $theme_screenshot : IMAGES.'imagenotfound.jpg',
                        'author'      => '',
                        'license'     => '',
                        'version'     => '',
                        'description' => $locale['theme_1035']
                    ];
                }
            }
        }
        krsort($data);
        foreach ($data as $status => $themes) {
            foreach ($themes as $theme_name => $theme_data) {
                echo "<div class='list-group'><div class='list-group-item'>";
                echo '<div class="row">';

                echo "<div class='col-xs-12 col-sm-2'>".thumbnail($theme_data['screenshot'], '150px')."</div>";

                echo '<div class="col-xs-12 col-sm-7">';
                echo "<h4 class='m-t-0 strong text-dark'>".($status == TRUE ? "<i class='fa fa-diamond fa-fw'></i>" : "").$theme_data['title']."</h4>";

                if (!empty($theme_data['description'])) {
                    echo "<div class='display-block m-t-10 m-b-10'>".$theme_data['description']."</div>";
                }
                if (!empty($theme_data['license'])) {
                    echo "<span class='badge display-inline-block m-r-10'><i class='fa fa-file fa-fw' title='".$locale['theme_1013']."'></i> ".$theme_data['license']."</span>";
                }
                if (!empty($theme_data['readme'])) {
                    echo "<a class='badge display-inline-block m-r-10' title='".$locale['theme_1036']."' target='_blank' href='".$theme_data['readme']."'><i class='fa fa-book fa-fw'></i> ".$locale['theme_1036']."</a>";
                }
                if (!empty($theme_data['version'])) {
                    echo "<span class='badge display-inline-block m-r-10'><i class='fa fa-code-fork fa-fw' title='".$locale['theme_1014']."'></i> ".$theme_data['version']."</span>";
                }
                if (!empty($theme_data['author'])) {
                    echo "<span class='badge display-inline-block m-r-10'><i class='fa fa-user fa-fw'></i> ".$theme_data['author']."</span>";
                }
                if (!empty($theme_data['web'])) {
                    echo "<a class='badge display-inline-block' target='_blank' title='".$locale['theme_1015']."' href='".$theme_data['web']."'><i class='fa fa-globe fa-fw'></i> ".$locale['theme_1015']."</a>";
                }
                echo "<div class='m-t-10'>";
                if ($status == TRUE) {
                    echo "<strong>".$locale['theme_1006']."</strong><br/>";
                }
                if (!empty($theme_data['widgets']) == TRUE) {
                    echo "<small>".$locale['theme_1027'].$locale['yes']."</small>";
                }
                echo "</div>";
                echo '</div>';

                echo '<div class="col-xs-12 col-sm-3">';
                if ($status == TRUE) {
                    echo "<a class='pull-right-lg btn btn-primary btn-sm' href='".FUSION_SELF.$aidlink."&action=manage&theme=".$theme_name."'><i class='fa fa-cog fa-fw'></i> ".$locale['theme_1005']."</a>";
                } else {
                    echo "<a class='pull-right-lg btn btn-default btn-sm' href='".FUSION_SELF.$aidlink."&section=list&action=set_active&theme=".$theme_name."'><i class='fa fa-diamond fa-fw'></i> ".$locale['theme_1012']."</a>";
                }
                echo '</div>';

                echo '</div>';

                echo "</div></div>";
                unset($theme_data);
            }
        }
    }

    /**
     * Verify that theme exist and not active
     *
     * @param string $theme_name
     * @param bool   $admin
     *
     * @return bool
     */
    static function theme_installable($theme_name, $admin = FALSE) {
        $folder = $admin == TRUE ? ADMIN_THEMES : THEMES;
        $atheme = $admin == TRUE ? 'acp_' : '';
        $atheme_ = $admin == TRUE ? 'admin_theme' : 'theme';

        return (
            is_dir($folder.$theme_name) &&
            file_exists($folder.$theme_name.'/'.$atheme.'theme.php') &&
            file_exists($folder.$theme_name.'/'.$atheme.'styles.css') &&
            fusion_get_settings($atheme_) !== $theme_name
        );
    }

    public static function admin_themes_list() {
        $locale = fusion_get_locale();
        $aidlink = fusion_get_aidlink();
        $settings = fusion_get_settings();

        if (isset($_GET['action']) && $_GET['action'] == "set_active" && isset($_GET['theme']) && $_GET['theme'] !== "") {
            $theme_name = form_sanitizer($_GET['theme']);
            if (self::theme_installable($theme_name, TRUE)) {
                $result = dbquery("UPDATE ".DB_SETTINGS." SET settings_value='".$theme_name."' WHERE settings_name='admin_theme'");
                if ($result) {
                    redirect(FUSION_SELF.$aidlink.'&section=admin_themes');
                }
            }
        }

        $data = [];
        $_dir = makefilelist(ADMIN_THEMES, ".|..", TRUE, "folders");
        foreach ($_dir as $folder) {
            $status = $settings['admin_theme'] == $folder ? 1 : 0;
            $themefolder = ADMIN_THEMES.$folder.'/';
            $theme_screenshot = file_exists($themefolder.'screenshot.png') ? $themefolder.'screenshot.png' : $themefolder.'screenshot.jpg';
            $data[$status][$folder] = [
                'title'      => $folder,
                'screenshot'  => file_exists($theme_screenshot) ? $theme_screenshot : IMAGES.'imagenotfound.jpg',
            ];
        }

        krsort($data);
        echo '<div class="row">';
        foreach ($data as $status => $themes) {
            foreach ($themes as $theme_name => $theme_data) {
                echo '<div class="col-xs-12 col-sm-6 col-lg-3">';
                    echo '<div class="panel panel-default"><div class="panel-body">';
                        echo '<img class="img-responsive" src="'.$theme_data['screenshot'].'" alt="'.$theme_name.'">';
                        echo '<h3>'.$theme_data['title'].'</h3>';

                        if ($status == 0) {
                            echo '<a class="btn btn-primary btn-block" href="'.FUSION_SELF.$aidlink.'&section=admin_themes&action=set_active&theme='.$theme_name.'">'.$locale['theme_1012'].'</a>';
                        }
                    echo '</div></div>';
                echo '</div>';
            }
        }
        echo '</div>';
    }

    public static function theme_uploader() {
        $defender = \defender::getInstance();
        $locale = fusion_get_locale();
        $aidlink = fusion_get_aidlink();

        require_once INCLUDES."infusions_include.php";

        if (isset($_POST['upload'])) {
            $target_folder = THEMES;
            $max_size = 5 * 1000 * 1000;
            $upload = upload_file('theme_files', '', $target_folder, ',.zip', $max_size);
            if ($upload['error'] != '0') {
                $defender->stop();
                switch ($upload['error']) {
                    case 1:
                        addNotice('danger', sprintf($locale['theme_error_001'], parsebytesize($max_size)));
                        break;
                    case 2:
                        addNotice('danger', $locale['theme_error_002']);
                        break;
                    case 3:
                        addNotice('danger', $locale['theme_error_003']);
                        break;
                    case 4:
                        addNotice('danger', $locale['theme_error_004']);
                        break;
                    default :
                        addNotice('danger', $locale['theme_error_003']);
                }
            } else {
                $target_file = $target_folder.$upload['target_file'];
                if (is_file($target_file)) {
                    $path = pathinfo(realpath($target_file), PATHINFO_DIRNAME);
                    if (class_exists('ZipArchive')) {
                        $zip = new \ZipArchive();
                        if ($zip->open($target_file) === TRUE) {
                            // checks if first folder is theme.php
                            if ($zip->locateName('theme.php', \ZipArchive::FL_NODIR) !== FALSE) {
                                // extract it to the path we determined above
                                $zip->extractTo($path);
                                addNotice('success', $locale['theme_success_001']);
                            } else {
                                $defender->stop();
                                addNotice('danger', $locale['theme_error_009']);
                            }
                            $zip->close();
                        } else {
                            addNotice('danger', $locale['theme_error_005']);
                        }
                    } else {
                        addNotice('warning', $locale['theme_error_006']);
                    }
                    @unlink($target_file);
                    redirect(FUSION_SELF.$aidlink.'&section=list');
                }
            }
        }

        if (isset($_POST['uploadadmintheme'])) {
            $target_folder = THEMES.'admin_themes/';
            $max_size = 5 * 1000 * 1000;
            $upload = upload_file('admintheme_files', '', $target_folder, ',.zip', $max_size);
            if ($upload['error'] != '0') {
                $defender->stop();
                switch ($upload['error']) {
                    case 1:
                        addNotice('danger', sprintf($locale['theme_error_001'], parsebytesize($max_size)));
                        break;
                    case 2:
                        addNotice('danger', $locale['theme_error_002']);
                        break;
                    case 3:
                        addNotice('danger', $locale['theme_error_003']);
                        break;
                    case 4:
                        addNotice('danger', $locale['theme_error_004']);
                        break;
                    default :
                        addNotice('danger', $locale['theme_error_003']);
                }
            } else {
                $target_file = $target_folder.$upload['target_file'];
                if (is_file($target_file)) {
                    $path = pathinfo(realpath($target_file), PATHINFO_DIRNAME);
                    if (class_exists('ZipArchive')) {
                        $zip = new \ZipArchive();
                        if ($zip->open($target_file) === TRUE) {
                            // checks if first folder is theme.php
                            if ($zip->locateName('acp_theme.php', \ZipArchive::FL_NODIR) !== FALSE) {
                                // extract it to the path we determined above
                                $zip->extractTo($path);
                                addNotice('success', $locale['theme_success_001']);
                            } else {
                                $defender->stop();
                                addNotice('danger', $locale['theme_error_009']);
                            }
                            $zip->close();
                        } else {
                            addNotice('danger', $locale['theme_error_005']);
                        }
                    } else {
                        addNotice('warning', $locale['theme_error_006']);
                    }
                    @unlink($target_file);
                    redirect(FUSION_SELF.$aidlink.'&section=admin_themes');
                }
            }
        }

        echo openform('uploadthemeform', 'post', FUSION_SELF.$aidlink, ['enctype' => 1, 'max_tokens' => 1]);
        openside($locale['theme_1010']);
        echo form_fileinput('theme_files', '', '', ['type' => 'object', 'preview_off' => TRUE,]);
        echo form_button('upload', $locale['theme_1007'], 'upload', ['class' => 'btn btn-primary']);
        closeside();

        openside($locale['theme_1011a']);
        echo form_fileinput('admintheme_files', '', '', ['type' => 'object', 'preview_off' => TRUE,]);
        echo form_button('uploadadmintheme', $locale['theme_1007a'], 'uploadadmintheme', ['class' => 'btn btn-primary']);
        closeside();
        echo closeform();
    }
}
