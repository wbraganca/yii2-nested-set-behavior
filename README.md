Nested Set behavior for Yii 2
=============================

This extension allows you to get functional for nested set trees.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```sh
php composer.phar require wbraganca/yii2-nested-set-behavior "*"
```

or add

```json
"wbraganca/yii2-nested-set-behavior": "*"
```

to the require section of your `composer.json` file.

Configuring
--------------------------

First you need to configure model as follows:

```php
use wbraganca\behaviors\NestedSetBehavior;
use wbraganca\behaviors\NestedSetQuery; 

class Category extends ActiveRecord
{
    public function behaviors()
    {
        return [
            [
                'class' => NestedSetBehavior::className(),
                // 'rootAttribute' => 'root',
                // 'levelAttribute' => 'level',
                // 'hasManyRoots' => true
            ],
        ];
    }

    public static function find()
    {
        return new NestedSetQuery(get_called_class());
    }
}
```

There is no need to validate fields specified in `leftAttribute`,
`rightAttribute`, `rootAttribute` and `levelAttribute` options. Moreover,
there could be problems if there are validation rules for these. Please
check if there are no rules for fields mentioned in model's rules() method.

In case of storing a single tree per database, DB structure can be built with
`schema/schema.sql`. If you're going to store multiple trees you'll need
`schema/schema-many-roots.sql`.

By default `leftAttribute`, `rightAttribute` and `levelAttribute` values are
matching field names in default DB schemas so you can skip configuring these.

There are two ways this behavior can work: one tree per table and multiple trees
per table. The mode is selected based on the value of `hasManyRoots` option that
is `false` by default meaning single tree mode. In multiple trees mode you can
set `rootAttribute` option to match existing field in the table storing the tree.

Selecting from a tree
---------------------

In the following we'll use an example model `Category` with the following in its
DB:

~~~
- 1. Mobile phones
    - 2. iPhone
    - 3. Samsung
        - 4. X100
        - 5. C200
    - 6. Motorola
- 7. Cars
    - 8. Audi
    - 9. Ford
    - 10. Mercedes
~~~

In this example we have two trees. Tree roots are ones with ID=1 and ID=7.

### Getting all roots

```php
$roots = Category::find()->roots()->all();
```

Result:

Array of Active Record objects corresponding to Mobile phones and Cars nodes.

### Getting all descendants of a node

```php
$category = Category::findOne(1);
if ($category) {
    $descendants = $category->descendants()->all();
    var_dump($descendants);
}
```

Result:

Array of Active Record objects corresponding to iPhone, Samsung, X100, C200 and Motorola.

### Getting all children of a node

```php
$category = Category::findOne(1);
if ($category) {
    $descendants = $category->children()->all();
    var_dump($descendants);
}
```

Result:

Array of Active Record objects corresponding to iPhone, Samsung and Motorola.

### Getting all ancestors of a node

```php
$category = Category::findOne(5);
if ($category) {
    $ancestors = $category->ancestors()->all();
    var_dump($ancestors);
}
```

Result:

Array of Active Record objects corresponding to Samsung and Mobile phones.

### Getting parent of a node

```php
$category = Category::findOne(9);
if ($category) {
    $parent = $category->parent()->one();
    var_dump($parent);
}
```

Result:

Array of Active Record objects corresponding to Cars.

### Getting node siblings

Using `NestedSet::prev()` or
`NestedSet::next()`:

```php
$category = Category::findOne(9);
if ($category) 
    $nextSibling = $category->next()->one();
}
```

Result:

Array of Active Record objects corresponding to Mercedes.

### Getting the whole tree

You can get the whole tree using standard AR methods like the following.

For single tree per table:

```php
Category::find()->addOrderBy('lft')->all();
```

For multiple trees per table:

```php
Category::find()->andWhere('root = ?', [$root_id])->addOrderBy('lft')->all();
```

Modifying a tree
----------------

In this section we'll build a tree like the one used in the previous section.

### Creating root nodes

You can create a root node using `NestedSet::saveNode()`.
In a single tree per table mode you can create only one root node. If you'll attempt
to create more there will be CException thrown.

```php
$root = new Category;
$root->title = 'Mobile Phones';
$root->saveNode();

$root = new Category;
$root->title = 'Cars';
$root->saveNode();
```

Result:

~~~
- 1. Mobile Phones
- 2. Cars
~~~

### Adding child nodes

There are multiple methods allowing you adding child nodes. To get more info
about these refer to API. Let's use these
to add nodes to the tree we have:

```php
$category1 = new Category;
$category1->title = 'Ford';

$category2 = new Category;
$category2->title = 'Mercedes';

$category3 = new Category;
$category3->title = 'Audi';

$root = Category::findOne(1);
$category1->appendTo($root);
$category2->insertAfter($category1);
$category3->insertBefore($category1);
```

Result:

~~~
- 1. Mobile phones
    - 3. Audi
    - 4. Ford
    - 5. Mercedes
- 2. Cars
~~~

Logically the tree above doesn't looks correct. We'll fix it later.

```php
$category1 = new Category;
$category1->title = 'Samsung';

$category2 = new Category;
$category2->title = 'Motorola';

$category3 = new Category;
$category3->title = 'iPhone';

$root = Category::findOne(2);
$category1->appendTo($root);
$category2->insertAfter($category1);
$category3->prependTo($root);
```

Result:

~~~
- 1. Mobile phones
    - 3. Audi
    - 4. Ford
    - 5. Mercedes
- 2. Cars
    - 6. iPhone
    - 7. Samsung
    - 8. Motorola
~~~

```php
$category1 = new Category;
$category1->title = 'X100';

$category2 = new Category;
$category2->title = 'C200';

$node = Category::findOne(3);
$category1->appendTo($node);
$category2->prependTo($node);
```

Result:

~~~
- 1. Mobile phones
    - 3. Audi
        - 9. С200
        - 10. X100
    - 4. Ford
    - 5. Mercedes
- 2. Cars
    - 6. iPhone
    - 7. Samsung
    - 8. Motorola
~~~

Moving a node making it a new root
---------------------------------

There is a special `moveAsRoot()` method that allows moving a node and making it
a new root. All descendants are moved as well in this case.

Example:

```php
$node = Category::findOne(10);
$node->moveAsRoot();
```

Recursive tree traversal
------------------------

```php
Category::find()->options();     // List all the tree
Category::find()->options(1);    // List all category in tree with root.id=1
Category::find()->options(1, 3); // List 3 levels of category in tree with root.id=1
```

Data format for [Fancytree](https://github.com/wbraganca/yii2-fancytree-widget).
-------------------------

```php
Category::find()->dataFancytree();     // List all the tree
Category::find()->dataFancytree(1);    // List all category in tree with root.id=1
Category::find()->dataFancytree(1, 3); // List 3 levels of category in tree with root.id=1
```
