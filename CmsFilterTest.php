<?php
/**
 * 存储方式一：复合对象结构
 */
//简单条件
class Cond {
    var $left;
    var $op;
    var $right;
    public function __construct($left, $op, $right) {
        $this->left = $left;
        $this->op = $op;
        $this->right = $right;
    }
    static public function create($left, $op, $right){
        $rc = new ReflectionClass(__CLASS__);
        return $rc->newInstanceArgs(func_get_args());
    }
}

//复合条件
class Conds {
    var $logic;// & |
    var $pool;// array<Cond|Conds>
    public function __construct($logic, array $pool) {
        $this->logic = $logic;
        $this->pool = $pool;
    }
    public function append(Cond $cond){
        array_push($this->pool, $cond);
    }
    static public function create($logic, $conds=array()){
        $rc = new ReflectionClass(__CLASS__);
        return $rc->newInstanceArgs(func_get_args());
    }
}


//条件解码器
class CondsParser {
    public function parse($conds){
        
    }
}


/*
 * 表 tab (
 *      a : int
 *      b : string
 *      c : datetime
 *      d : int
 * )
 * 筛选条件：a in (1,2) && b like '%hehe%' && c > '2015-06-25'
 */
$conds = Conds::create('&', array(
    Cond::create('A.a', 'in', '(1,2)'),
	Cond::create('A.b', 'like', '%hehe%'),
	Cond::create('A.c', '>', '2015-06-25'),
    Conds::create('|', array(
        Cond::create('A.d', '<', '0'),
        Cond::create('A.d', '>', '100'),
    )),
));
dump($conds);




/**
 * 存储方式二（简洁易用，推荐）
 */
print '<br><br><br>------------------------------条件结构与解码测试-------------------------<br><br><br>';
//条件结构
$conds = array('&',
	array('A.a', 'in', '(1,2)'),
    array('A.b', 'like', '%hehe中文%'),
    array('A.c', '>', '2015-06-25'),
    array('|',
        array('A.d', '<', 0),
        array('A.d', '>', 100),
    )
);
//JS格式
echo json_encode($conds).'<br>';
//条件解码
function parseConds($conds, $condsType = 'array'){
        if ('json' == $condsType) { $conds = json_decode($conds, 1); }
        $conds = $this->buildRealConds($conds);
        
        $where = '';
        $len = count($conds);
        $blank = $conds[0] == '&' ? '1=1' : ($conds[0] != '|' ?: '1<>1');
        $logic = $conds[0] == '&' ? 'AND' : ($conds[0] != '|' ?: 'OR');
        foreach ($conds as $k => $c) {
            if ($k == 0) {
                $where .= " ( {$blank} ";
            } else {
                if (in_array($c[0], array('&', '|'))) {
                    $where .= " {$logic} " . parseConds($c);
                } else {
                    $inOrNot = strcasecmp($c[1], 'IN') && strcasecmp($c[1], 'NOT IN');
                    $quote = $inOrNot ? (is_string($c[2]) ? '"' : '') : '';
                    $where .= " {$logic} {$c[0]} {$c[1]} {$quote}{$c[2]}{$quote} ";
                }
            }
            if ($k == $len - 1) $where .= ' ) ';
        }
        return $where;
}
//测试
$conds2 = parseConds($conds);
print $conds2.'<br>';






/**
 * 筛选器结构
 * 依次是：选取域、条件、排序、条目限制
 */
print '<br><br><br>------------------------------筛选器结构与解码测试-------------------------<br><br><br>';
$filter = array(
    'id' => 1,
    'name' => '筛选器名称',
    'desc' => '筛选器描述',
    'struct' => array(
        'from' => 'A',
        'fields' => '*',
    	'conds' => $conds,
    	'order' => 'A.a DESC, A.b ASC',
    	'limit' => '2, 5',
    ),
);
//筛选器解码
function parseFilter($filter){
    $s = $filter['struct'];
    $str = "SELECT {$s['fields']} FROM {$s['from']} 
            WHERE " . parseConds($s['conds']) . "
            ORDER BY {$s['order']} LIMIT {$s['limit']}";
    return $str;
}
//测试
$result = parseFilter($filter);
echo $result.'<br>';



/**
 * 查看序列化的筛选器
 */
print '<br><br><br>------------------------------序列化筛选器测试-------------------------<br><br><br>';
echo serialize($filter).'<br>';


/**
 * 管理筛选器
 */
print '<br><br><br>------------------------------管理筛选器测试-------------------------<br><br><br>';
class FilterManager {
    static public function save($filter){
        file_put_contents(DI_CACHE_PATH.$filter['id'].'.cmsfilter', serialize($filter));
    }
    static public function read($id){
        $f = DI_CACHE_PATH.$id.'.cmsfilter';
        if (! file_exists($f)) return false;
        return unserialize(file_get_contents($f));
    }
}
//测试
$id = $filter['id'];
FilterManager::save($filter);
$tmpFilter = FilterManager::read($id);
dump($tmpFilter);






/**
 * 前端传入参数测试
 */
print '<br><br><br>------------------------------前端传入参数测试-------------------------<br><br><br>';
$conds = '["&",["A.a","in","(1,2)"],["A.b","like","%hehe\u4e2d\u6587%"],["A.c",">","2015-06-25"],["|",["A.d","<",0],["A.d",">",100]]]';
$conds = json_decode($conds, 1);
dump($conds);

