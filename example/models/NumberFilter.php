<?php

namespace app\models;

use Yii;

const BASE = 'nums';
const DEVIDE = 'nums_devides_';
const ENDWITH = 'nums_ends_';

class NumberFilter
{
  static function getNums()
  {
    return [2, 3, 5, 7];
  }

  static function prepareData()
  {
    $nums = self::getNums();
    $rf = Yii::$app->redisfilter;
    if ($rf->getTagTotalByZset(BASE)) {
      // print_r($rf->getTagTotalByZset(BASE));die;
      return false;
    }

    for ($i = 0; $i < 10000; $i++) {
      $rf->setTag($i, BASE, $i); // full list
      foreach ($nums as $d) {
        if (($i % $d) === 0) {
          $rf->setTag($i, DEVIDE . $d, $i); // nums_devide_5 contain numbers devied by 5
        }
        if (($i % 10) === $d) {
          $rf->setTag($i, ENDWITH . $d, $i);  // nums_devide_5 contain numbers ends with 5
        }
      }
    }
    return true;
  }

  static function getPageData($page = 1, $pageSize = 10)
  {
    $nums = self::getNums();
    $query = [];
    foreach (['devides', 'ends'] as $type) {
      foreach ($nums as $d) {
        if (self::isOn($type, $d)) {
          if (!count($query)) {
            $query = ['nums_' . $type . '_' . $d];
          } else {
            $query = ['nums_' . $type . '_' . $d => $query];
          }
        }
      }
    }

    $page0 = intval($page) - 1;
    $pageSize0 = intval($pageSize);
    $desc = false;
    $rf = Yii::$app->redisfilter;

    if (!count($query)) {
      return [
        'list' => $rf->getTagListByZset(BASE, $page0 * $pageSize0, $pageSize0, $desc),
        'total' => $rf->getTagTotalByZset(BASE),
        'page' => $page0 + 1,
        'pageSize' => $pageSize0,
      ];
    }
    // print_r($query);die;
    $zsetName = $rf->getZset($query);
    $list = $rf->getTagListByZset($zsetName, $page0 * $pageSize0, $pageSize0, $desc);
    $total = $rf->getTagTotalByZset($zsetName);
    return [
      'list' => $list,
      'total' => $total,
      'page' => $page0 + 1,
      'pageSize' => $pageSize0,
    ];
  }

  static function getLinks()
  {
    $nums = self::getNums();
    $filters = self::getFilters();
    $rtn = ['devides' => [], 'ends' => []];
    foreach ($rtn as $type => $v) {
      foreach ($nums as $d) {
        $on = self::isOn($type, $d);
        $tf = $filters;
        $bit = self::getFilterMap()[$type][$d];
        $tf[$type] = $on ? $tf[$type] & ~$bit : $tf[$type] | $bit;

        array_unshift($tf, 'site/index');
        $rtn[$type][] = [
          'on' => $on,
          'num' => $d,
          'param' => $tf,
        ];
      }
    }
    return $rtn;
  }

  static function isOn($type, $num)
  {
    $filters = self::getFilters();
    $map = self::getFilterMap();
    $bits = $map[$type];
    $bit = $bits[$num];
    return $filters[$type] & $bit;
  }

  static function getFilters()
  {
    $devides = intval(Yii::$app->request->getQueryParam('devides', 0));
    $ends = intval(Yii::$app->request->getQueryParam('ends', 0));

    return ['devides' => $devides, 'ends' => $ends];
  }

  static function getFilterMap()
  {
    $nums = self::getNums();
    $t = 1;
    $rtn = ['devides' => [], 'ends' => []];
    foreach ($nums as $n) {
      $rtn['devides'][$n] = $t;
      $rtn['ends'][$n] = $t;
      $t = $t << 1;
    }
    return $rtn;
  }
}
