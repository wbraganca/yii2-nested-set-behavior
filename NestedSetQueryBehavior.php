<?php
/**
 * @link https://github.com/wbraganca/yii2-nested-set-behavior
 * @copyright Copyright (c) 2013 Alexander Kochetov
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace wbraganca\behaviors;

use yii\base\Behavior;

/**
 * @author Alexander Kochetov <creocoder@gmail.com>
 */
class NestedSetQueryBehavior extends Behavior
{
    /**
     * @var ActiveQuery the owner of this behavior.
     */
    public $owner;

    /**
     * Gets root node(s).
     * @return ActiveRecord the owner.
     */
    public function roots()
    {
        /** @var $modelClass ActiveRecord */
        $modelClass = $this->owner->modelClass;
        $model = new $modelClass;
        $this->owner->andWhere($modelClass::getDb()->quoteColumnName($model->leftAttribute) . '=1');
        unset($model);
        return $this->owner;
    }

    public function options($root = 0, $level = null)
    {
        $res = [];
        if (is_object($root)) {
            $res[$root->{$root->idAttribute}] = str_repeat('—', $root->{$root->levelAttribute} - 1) 
                . ((($root->{$root->levelAttribute}) > 1) ? '›': '')
                . $root->{$root->titleAttribute};

            if ($level) {
                foreach ($root->children()->all() as $childRoot) {
                    $res += $this->options($childRoot, $level - 1);
                }
            } elseif (is_null($level)) {
                foreach ($root->children()->all() as $childRoot) {
                    $res += $this->options($childRoot, null);
                }
            }
        } elseif (is_scalar($root)) {
            if ($root == 0) {
                foreach ($this->roots()->all() as $rootItem) {
                    if ($level) {
                        $res += $this->options($rootItem, $level - 1);
                    } elseif (is_null($level)) {
                        $res += $this->options($rootItem, null);
                    }
                }
            } else {
                $modelClass = $this->owner->modelClass;
                $model = new $modelClass;
                $root = $modelClass::find()->andWhere([$model->idAttribute => $root])->one();
                if ($root) {
                    $res += $this->options($root, $level);
                }
                unset($model);
            }
        }
        return $res;
    }
}
