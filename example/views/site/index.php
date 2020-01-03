<?php

use yii\helpers\Url;
/* @var $this yii\web\View */

$this->title = 'My Yii Application';

?>
<style>
  td {    
    padding: 5px 10px;
  }

  .filter {
    padding: 5px 10px;
    border: 1px solid greenyellow;
  }

  .filter.on {
    border: 1px solid green;
    background: green;
    color: #fff;
  }
</style>
<div class="site-index">
  <?php if ($prepare) : ?>
    <p> data installed! </p>
  <?php endif ?>
  <table>
    <tbody>
      <?php foreach ($links as $type => $filters) : ?>
        <tr>
          <th><?= $type ?></th>
          <?php foreach ($filters as $filter) : ?>
            <td><a class="filter <?= $filter['on'] ?>" href="<?= Url::to($filter['param']) ?>"><?= $filter['num'] ?></a></td>
          <?php endforeach ?>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <table>
    <tbody>
      <?php foreach ($data['list'] as $num) : ?>
        <tr>
          <td><?= $num ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
  <p>page:<?=$data['page']?>(page size:<?=$data['pageSize']?> | total:<?=$data['total']?>)</p>
</div>