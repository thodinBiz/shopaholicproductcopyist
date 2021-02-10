<?php
/**
 * Created by PhpStorm.
 * User: Thodin
 * Date: 02.02.2021
 * Time: 12:12
 */

namespace Thodin\ShopaholicProductCopyist\Controllers;

use Backend\Classes\Controller;

/**
 * Class Categories
 * @package Thodin\ShopaholicProductCopyist\Controllers
 */
class Categories extends Controller
{
    /**
     * @return array
     */
    public function onGetOptions()
    {
        return ['result' => []];
    }
}
