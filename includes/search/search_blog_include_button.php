<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: search_blog_include_button.php
| Author: Robert Gaudyn (Wooya)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
if (!defined("IN_FUSION")) {
    die("Access Denied");
}
if (db_exists(DB_BLOG)) {
    $form_elements['blog']['enabled'] = array("datelimit", "fields1", "fields2", "fields3", "sort", "order1", "order2", "chars");
    $form_elements['blog']['disabled'] = array();
    $form_elements['blog']['display'] = array();
    $form_elements['blog']['nodisplay'] = array();
    $radio_button['blog'] = form_checkbox('stype', fusion_get_locale('n400', LOCALE.LOCALESET."search/blog.php"), $_GET['stype'],
                                        array(
                                            'type'      => 'radio',
                                            'value'     => 'blog',
                                            'reverse_label' => TRUE,
                                            'onclick' => 'display(this.value)',
                                            'input_id' => 'blog'
                                          )
                              );
}
