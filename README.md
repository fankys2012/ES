版本匹配说明
Elasticsearch-PHP Branch	PHP Version
    6.0				>= 7.0.0
    5.0				>= 5.6.6
    2.0				>= 5.4.0
    0.4, 1.0			>= 5.3.9

Elasticsearch Version	Elasticsearch-PHP Branch
    >= 6.0			6.0
    >= 5.0, < 6.0		5.0
    >= 2.0, < 5.0		1.0 or 2.0
    >= 1.0, < 2.0		1.0 or 2.0
    <= 0.90.x		0.4

1：java jdk 安装
	
	JAVA_HOME=/usr/local/jdk1.8.0_131
	PATH=$PATH:$JAVA_HOME/bin
	CLASSPATH=.:$JAVA_HOME/lib/dt.jar:$JAVA_HOME/lib/tools.jar

	export PATH JAVA_HOME CLASSPATH

2：elasticsearch 安装 
    elsearch下载：https://www.elastic.co/downloads/past-releases/elasticsearch-6-3-0

    创建elasticsearch用户/用户分组：
    groupadd elasticsearch 
    useradd elasticsearch -g elasticsearch -p elasticsearch 

    权限： chown elasticsearch:elasticsearch -R /data/elasticsearch-6.3.0

    修改limit限制（5.0以后都要修改）
        > vi /etc/security/limits.conf

        * soft nofile 65536
        * hard nofile 65536
        * soft core unlimited
        * hard core unlimited

        > echo "vm.max_map_count=262144" >> /etc/sysctl.conf

        >sysctl -p

        > vi /etc/security/limits.d/90-nproc.conf 

        * soft nproc 2048
        * elasticsearch soft nofile    65536
        * elasticsearch hard nofile    65536

        > su elasticsearch   (所有操作必须在普通用户下)
        > vim config/elasticsearch.yml
            cluster.name: starcor-cluster   #(集群的名字,唯一）
            node.name:  es01  # (节点的名字)
            network.host: 192.168.90.71  #(端口绑定的IP）
            http.port: 9200  #（绑定的端口）
                # 跨域
            http.cors.enabled: true
            http.cors.allow-origin: "*"

	    bootstrap.system_call_filter: false #Centos6不支持SecComp 注意要在memory_lock下面

    启动 /data/elasticsearch6-3.0/bin/elasticsearch -d  (需要切换到elasticsearch用户)
3: 中文分词插件： 
    https://github.com/medcl/elasticsearch-analysis-ik
    直接使用文档上第种方案
4：中文拼音插件
    https://github.com/medcl/elasticsearch-analysis-pinyin/tree/v6.3.0

    解压后进入目录 执行 mvn package （需要安装maven）
    打包成功以后，会生成一个target文件夹 target/releases目录下，找到elasticsearch-analysis-xxx.zip 并将该压缩包解压到elasticsearch/plugin/pinyin 目录下

5：php扩展 
    文档库：https://github.com/elastic/elasticsearch-php
    composer 获取相应php扩展库 

    composer 安装 百度

    修改 composer.json 
    {
        "require": {
            "elasticsearch/elasticsearch": "~6.0"
        }
    }

    到composer 目录执行 （composer.phar 所在目录）
    /usr/local/php/bin/php composer.phar install --no-dev



6：调整深度翻页限制
    curl -XPUT http://127.0.0.1:9200/my_index/_settings -d '{ "index" : { "max_result_window" : 500000}}'

7：query_dsl domian 
https://www.elastic.co/guide/en/elasticsearch/reference/current/full-text-queries.html

8：排序 https://www.cnblogs.com/richaaaard/p/5254988.html
 function-score-query： https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html