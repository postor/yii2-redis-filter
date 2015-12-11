# yii2-redis-filter
ordering,paging,categorizing your items use via sorted set 利用redis的有序集合实现排序、分页、分类

##install安装

```
composer require postor/yii2-reids-filter
```

##config配置

```
'components' => [
        ....,
        'redis' => [
	        'class' => 'yii\redis\Connection',
	        'hostname' => REDIS_HOST,
	        'port' => REDIS_PORT,
	        'database' => REDIS_DB,
        ],
        'redisfilter'=>[
          'class'=>'postor\redisfilter\RedisFilter',
          'redis'=>'redis',
        ],
        ....
```
##usage使用
\Yii::$app->redisfilter->getTagList($tag,$offset,$limit); $tag param： 
- string, just the tag set 字符串就是对应的tag集合
- array, deep loop each array and interstore key and value, then unionstore all element results. 数组情况则key与value取交集，然后所有数组元素取并集，可以嵌套
 
['a','b'=>['c'=>['d','e'=>'f']]] means a|(b&c&(d|(e&f))

```
//set tags 设置标签
$rf = \Yii::$app->redisfilter;
$rf->setTag($a->id, 'base', $a->created_time); //all article sort by created_time 所有文章按时间排序
$rf->setTag($a->id, 'grid'.$a->grid_id); //all article tag their location 所有文章按地区贴上标签
$rf->removeTag($b->id, 'base');

//filter tags 筛选标签
//by page 按分页
$ids = $rf->getTagList(['seta','setb'=>'setc'],$offset,$pageSize); // seta|(setb&setc) 
//$ids  array(11) { [0]=> string(7) "1385584" [1]=> string(7) "1385585" [2]=> string(7) "1385586" [3]=> string(7) "1385587" [4]=> string(7) "1385588" [5]=> string(7) "1385589" [6]=> string(7) "1515910" [7]=> string(7) "1515911" [8]=> string(7) "1515912" [9]=> string(7) "1515913" [10]=> string(7) "1515914" }

//by score 按分数
$zsetname = $rf->getZset(['base'=>'grid'.$gridid]); // base&gridX
$total = $rf->getTagTotalByZset($zsetname);
$ids = $rf->getTagListByScore($zsetname, time()-86400*3, time());


```
