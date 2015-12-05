<?php
/**
 * Redis Filter
 * User: postor@gmail.com
 */
namespace postor\redisfilter;

use yii\base\Component;
use Yii;
use yii\redis\Connection;
use yii\base\InvalidConfigException;

class RedisFilter extends Component {
	
	//注意tag中尽量不要使用"|"和"&"，因为它有可能产生歧义
	const DELEMITER_OR = '|';
	const DELEMITER_AND = '&';
	public $redis = null;

	/**
	 * 初始化	
	 */
	public function init(){
				parent::init();
        if (is_string($this->redis)) {
            $this->redis = Yii::$app->get($this->redis);
        } elseif (is_array($this->redis)) {
            if (!isset($this->redis['class'])) {
                $this->redis['class'] = Connection::className();
            }
            $this->redis = Yii::createObject($this->redis);
        }
        if (!$this->redis instanceof Connection) {
            throw new InvalidConfigException("Cache::redis must be either a Redis connection instance or the application component ID of a Redis connection.");
        }
	}
	
	/**
	 * 设置一个元素到标签，注意tag中尽量不要使用"|"和"&"，因为它有可能产生歧义
	 * @param string $id
	 * @param string $tag
	 * @param string $score
	 */
	public function setTag($id,$tag,$score = 0){
		$this->redis->zadd($tag,$score,$id);
	}
	
	/**
	 * 移除
	 * @param unknown_type $id
	 * @param unknown_type $tag
	 */
	public function removeTag($id,$tag){
		$this->redis->zrem($tag,$id);
	}
	
	/**
	 * 获取某个筛选，注意tag中尽量不要使用"|"和"&"，因为它有可能产生歧义
	 * @param mixed $tag
	 * 如果$tag类型是字符串则就取这个tag的内容
	 * 如果$tag类型是数字下标数组则每个数组元素之间为或关系	 
	 * 如果$tag类型是键值数组则key和value为与关系，value可以是字符串或数组，递归处理
	 * 
	 * @param int $offset
	 * @param int $limit
	 * @param bool $revert
	 */
	public function getTagList($tag,$offset = 0, $limit = 10, $revert = true){
		$zsetname = $this->getZset($tag);
		return $this->getTagListByZset($zsetname,$offset, $limit, $revert);
	}
	
	/**
	 * 根据sorted set获取列表
	 * @param unknown_type $zsetname
	 * @param unknown_type $offset
	 * @param unknown_type $limit
	 * @param unknown_type $revert
	 */
	public function getTagListByZset($zsetname,$offset = 0, $limit = 10, $revert = true){		
		if($revert){
			return $this->redis->zrevrange($zsetname,$offset,$offset+$limit-1);
		}else{
			return $this->redis->zrange($zsetname,$offset,$offset+$limit-1);
		}
	}
	
	/**
	 * 获取总数
	 * @param unknown_type $tag
	 */
	public function getTagTotal($tag){
		$zsetname = $this->getZset($tag);
		return $this->getTagTotalByZset($zsetname);		
	}
	
	/**
	 * 根据sorted set获取总数
	 * @param unknown_type $zsetname
	 */
	public function getTagTotalByZset($zsetname){
		return $this->redis->zcard($zsetname);
	}
	
	/**
	 * 获取一个范围内的
	 * @param unknown_type $zsetname
	 * @param unknown_type $start
	 * @param unknown_type $end
	 */
	public function getTagListByScore($zsetname,$start,$end){
		$revert = ($start>$end);
		if($revert){
			return $this->redis->zrevrangebyscore($zsetname,$start,$end);
		}else{
			return $this->redis->zrangebyscore($zsetname,$start,$end);
		}
	}
	
	/**
	 * 获取对应的zset名称
	 * @param mixed $tag
	 * @param string $base
	 * @return string
	 */
	public function getZset($tag){
		if(is_string($tag)){
			//是字符串
			return $tag;
		}else{
			$rtnArr = [];
			foreach ($tag as $key=>$value){
				if(($key+0) === $key){
					//数字下标
					$valueZset = $this->getZset($value);
					$rtnArr[] = $valueZset;
				}else{
					//键值对
					$keyZset = $this->getZset($key);
					$valueZset = $this->getZset($value);
					
					
					$tempKey = $this->checkQ($keyZset).self::DELEMITER_AND.$this->checkQ($valueZset);
					
					call_user_func_array([$this->redis,'zinterstore']
							, [$tempKey, 2,$keyZset,$valueZset]);
					
					$rtnArr[] = $tempKey;					
				}
			}
			if(count($rtnArr) === 1) return $rtnArr[0];
			$rtnArrS = [];
			foreach($rtnArr as $item){
				$rtnArrS[] = $this->checkQ($item);
			}
			
			$aZsetKey = implode(self::DELEMITER_OR, $rtnArrS);
						
			call_user_func_array([$this->redis,'zunionstore']
					, array_merge([$aZsetKey, count($rtnArr)],$rtnArr));
			return $aZsetKey;
		}
	}
	
	private function checkQ($str){
		if(strpos($str, self::DELEMITER_AND)===false 
				&& strpos($str, self::DELEMITER_OR)===false){
			return $str;
				}
				
		return '('.$str.')';		
	}
}