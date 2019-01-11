# 短语匹配 参考：https://zhuanlan.zhihu.com/p/25970549
{
    "query": {
        "match_phrase": {
            "name": {
                "query": "德华"
            }
        }
    }
}
#two
{
    "query": {
        "bool": {
            "must": [
                {
                    "match_phrase": {
                        "name": {
                            "query": "贤真"
                        }
                    }
                }
            ],
            "filter": [
                {
                    "term": {
                        "category": "star"
                    }
                }
            ]
        }
    }
}

#调整深度翻页限制
curl -XPUT http://127.0.0.1:9200/my_index/_settings -d '{ "index" : { "max_result_window" : 500000}}'

#query_dsl domian https://www.elastic.co/guide/en/elasticsearch/reference/current/full-text-queries.html

#排序 https://www.cnblogs.com/richaaaard/p/5254988.html

# function-score-query： https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html