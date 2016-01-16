<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(__DIR__ . '/../../../config.php');

\block_mbsnews\local\newshelper::process_notification_jobs();
