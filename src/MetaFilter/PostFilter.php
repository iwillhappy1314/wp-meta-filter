<?php

namespace Wenprise\MetaFilter;

use Nette\Utils\Html;

class PostFilter
{

    /**
     * 添加到的文章类型
     *
     * @var string
     */
    var $post_type = '';

    /**
     * 查询参数
     *
     * @var string
     */
    var $query_var = '';


    /**
     * 自定义字段 key
     *
     * @var string
     */
    var $meta_key = '';


    /**
     * 数据对比方式
     *
     * @var string
     */
    var $compare = '=';


    /**
     * 下拉选项数组
     *
     * @var array
     */
    var $options = [];


    /**
     * 设置选项空值
     *
     * @var array
     */
    var $header = [0 => '选择一项'];


    public function __construct()
    {
        $this->set_options();

        add_filter('restrict_manage_posts', [$this, 'add_field']);
        add_filter('parse_query', [$this, 'filter_query']);
    }


    /**
     * 设置文章类型
     *
     * @param $post_type
     *
     * @return $this
     */
    public function set_post_type($post_type)
    {
        $this->post_type = $post_type;

        return $this;
    }


    /**
     * 设置查询参数
     *
     * @param $query_var
     *
     * @return $this
     */
    public function set_query_var($query_var)
    {
        $this->query_var = $query_var;

        return $this;
    }


    /**
     * 设置自定义字段 key
     *
     * @param $meta_key
     *
     * @return $this
     */
    public function set_meta_key($meta_key)
    {
        $this->meta_key = $meta_key;

        return $this;
    }


    /**
     * 设置数据对比方式
     *
     * @param $compare
     *
     * @return $this
     */
    public function set_compare($compare)
    {
        $this->compare = $compare;

        return $this;
    }


    /**
     * 设置空置
     *
     * @param $header
     *
     * @return $this
     */
    public function set_header($header)
    {
        if ( ! is_array($header)) {
            $header = [0 => $header];
        }

        $this->header = $header;

        return $this;
    }


    /**
     * 获取选项数组
     *
     * @param $options
     *
     * @return $this
     */
    function set_options($options = [])
    {
        global $wpdb;

        if (empty($options)) {
            $options = $wpdb->get_col(
                $wpdb->prepare("
                SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
                LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = '%s'
                AND p.post_status IN ('publish', 'draft')
                ORDER BY pm.meta_value",
                    $this->meta_key
                )
            );

            $options_new = [];
            foreach ($options as $option) {
                $options_new[ $option ] = $option;
            }

            $options = $options_new;
        }

        $this->options = $this->header + (array)$options;

        return $this;
    }


    /**
     * 添加筛选表单
     */
    function add_field()
    {

        global $typenow;

        if ($this->post_type == $typenow) {

            $query_var = $this->query_var;
            $selected  = isset($_GET[ $query_var ]) ? $_GET[ $query_var ] : '';

            // 使用获取的筛选条件数据添加下拉表单
            $el = Html::el('select')
                      ->setAttribute('id', $this->query_var)
                      ->setAttribute('name', $this->query_var);

            foreach ($this->options as $value => $label) {
                $option = Html::el('option')
                              ->setAttribute('value', esc_attr($value))
                              ->addText(esc_attr($label));

                if ($value == $selected) {
                    $option->setAttribute('selected', 'selected');
                }

                $el->addHtml($option);
            }

            echo $el;

        }
    }


    /**
     * 过滤查询
     *
     * @param $query
     *
     * @return mixed
     */
    function filter_query($query)
    {

        global $pagenow;
        $post_type = isset($_GET[ 'post_type' ]) ? $_GET[ 'post_type' ] : '';

        if (is_admin() && $pagenow == 'edit.php' && $post_type == $this->post_type && isset($_GET[ $this->query_var ]) && $_GET[ $this->query_var ]) {

            $query->query_vars[ 'meta_key' ]     = $this->meta_key;
            $query->query_vars[ 'meta_value' ]   = $_GET[ $this->query_var ];
            $query->query_vars[ 'meta_compare' ] = $this->compare;
        }

        return $query;

    }

}