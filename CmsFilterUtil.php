<?php

class CmsFilterUtil {
    
    //解码条件部分
    static public function parseConds($conds, $condsType = 'array'){
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
                    $where .= " {$logic} " . $this->parseConds($c);
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
    
    //解码整个筛选器
    static public function parseFilter($filter){
        $s = $filter['struct'];
        $str = "SELECT {$s['fields']} FROM {$s['from']}
                WHERE " . self::parseConds($s['conds']) . "
                ORDER BY {$s['order']} LIMIT {$s['limit']}";
        return $str;
    }
    
    //测试1：条件结构解码
    static public function test1(){
        $conds = array('&',
            array('A.a', 'in', '(1,2)'),
            array('A.b', 'like', '%hehe中文%'),
            array('A.c', '>', '2015-06-25'),
            array('|',
                array('A.d', '<', 0),
                array('A.d', '>', 100),
            )
        );
        print '条件结构明细：<br>';
    }
    
}
