<?php
/*-------------------------------------------------------+
| PHPFusion Content Management System
| Copyright (C) PHP Fusion Inc
| https://www.phpfusion.com/
+--------------------------------------------------------+
| Filename: index.php
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
require_once __DIR__."/../../maincore.php";

$endpoints = [
    "username-check" => "username_validation.php",
];

if ($api = get("api")) {
    if (isset($endpoints[$api])) {

        require $endpoints[$api];

        fusion_apply_hook("fusion_filters");

    } else {
        throw new Exception("End point is faulty");
    }
} else {
    throw new Exception("API is not specified");
}
