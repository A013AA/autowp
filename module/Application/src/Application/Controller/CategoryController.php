<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

use Application\Paginator\Adapter\Zend1DbTableSelect;

use Car_Parent;
use Category;
use Category_Language;
use Picture;
use Users;

use Zend_Db_Expr;

class CategoryController extends AbstractActionController
{
    private $otherCategoryName = 'Other';

    /**
     * @var Category
     */
    private $categoryTable;

    /**
     * @var Category_Language
     */
    private $categoryLanguageTable;

    private $cache;

    public function __construct($cache)
    {
        $this->cache = $cache;

        $this->categoryTable = new Category();
        $this->categoryLanguageTable = new Category_Language();
    }

    public function indexAction()
    {
        $language = $this->language();

        $key = 'CATEGORY_INDEX46_' . $language;

        $categories = $this->cache->getItem($key, $success);
        if (!$success) {

            $categories = [];

            $rows = $this->categoryTable->fetchAll([
                'parent_id is null',
            ], 'short_name');

            foreach ($rows as $row) {

                $langRow = $this->categoryLanguageTable->fetchRow([
                    'language = ?'    => $language,
                    'category_id = ?' => $row->id
                ]);

                $categories[] = [
                    'id'             => $row->id,
                    'url'            => $this->url()->fromRoute('categories', [
                        'action'           => 'category',
                        'category_catname' => $row->catname,
                    ]),
                    'name'           => $langRow ? $langRow->name : $row->name,
                    'short_name'     => $langRow ? $langRow->short_name : $row->short_name,
                    'cars_count'     => $row->getCarsCount(),
                    'new_cars_count' => $row->getWeekCarsCount(),
                ];
            }

            $this->cache->setItem($key, $categories);
        }

        $pictures = $this->catalogue()->getPictureTable();
        foreach ($categories as &$category) {
            $category['top_picture'] = $pictures->fetchRow(
                $pictures->select(true)
                    ->join('category_car', 'pictures.car_id=category_car.car_id', null)
                    ->join('category_parent', 'category_car.category_id = category_parent.category_id', null)
                    ->where('pictures.type = ?', Picture::CAR_TYPE_ID)
                    ->where('pictures.status IN (?)', [Picture::STATUS_ACCEPTED, Picture::STATUS_NEW])
                    ->where('category_parent.parent_id = ?', $category['id'])
                    ->order([
                        new Zend_Db_Expr('pictures.perspective_id = 7 DESC'),
                        new Zend_Db_Expr('pictures.perspective_id = 8 DESC'),
                        new Zend_Db_Expr('pictures.perspective_id = 1 DESC')
                    ])
                    ->limit(1)
            );
        }

        return [
            'categories' => $categories
        ];
    }

    private function categoriesMenuActive(&$menu, $currentCategory, $isOther)
    {
        $activeFound = false;
        foreach ($menu as &$item) {
            $item['active'] = false;

            if (($item['isOther'] ? $isOther : !$isOther) && ($item['id'] == $currentCategory->id)) {
                $activeFound = true;
                $item['active'] = true;
            }
            if ($this->categoriesMenuActive($item['categories'], $currentCategory, $isOther)) {
                $activeFound = true;
                $item['active'] = true;
            }
        }

        return $activeFound;
    }

    private function categoriesMenu($parent, $language, $maxDeep)
    {
        $categories = [];

        if ($maxDeep > 0) {
            $filter = $parent ? [
                'parent_id = ?' => $parent->id
            ] : [
                'parent_id is null'
            ];
            $rows = $this->categoryTable->fetchAll($filter, 'short_name');
            foreach ($rows as $row) {
                $langRow = $this->categoryLanguageTable->fetchRow([
                    'language = ?'    => $language,
                    'category_id = ?' => $row['id']
                ]);

                $category = [
                    'id'             => $row->id,
                    'url'            => $this->url()->fromRoute('categories', [
                        'action'           => 'category',
                        'category_catname' => $row->catname,
                        'other'            => false,
                        'car_id'           => null,
                        'page'             => null
                    ]),
                    'name'           => $langRow ? $langRow->name : $row->name,
                    'short_name'     => $langRow ? $langRow->short_name : $row->short_name,
                    'cars_count'     => $row->getCarsCount(),
                    'new_cars_count' => $row->getWeekCarsCount(),
                    'categories'     => $this->categoriesMenu($row, $language, $maxDeep-1),
                    'isOther'        => false
                ];

                $categories[] = $category;
            }

            if ($parent && count($categories)) {
                $ownCarsCount = $parent->getOwnCarsCount();
                if ($ownCarsCount > 0) {
                    $categories[] = [
                        'id'             => $parent->id,
                        'url'            => $this->url()->fromRoute('categories', [
                            'action'           => 'category',
                            'category_catname' => $parent->catname,
                            'other'            => true,
                            'car_id'           => null,
                            'page'             => null
                        ]),
                        'short_name'     => $this->otherCategoryName,
                        'cars_count'     => $ownCarsCount,
                        'new_cars_count' => $parent->getWeekOwnCarsCount(),
                        'isOther'        => true,
                        'categories'     => []
                    ];
                }
            }
        }

        usort($categories, function ($a, $b) {
            if ($a["short_name"] == $this->otherCategoryName) {
                return 1;
            }
            if ($b["short_name"] == $this->otherCategoryName) {
                return -1;
            }
            return strcmp($a["short_name"], $b["short_name"]);
        });

        return $categories;
    }

    private function _categoryAction($callback)
    {
        $language = $this->language();

        $currentCategory = $this->categoryTable->fetchRow([
            'catname = ?' => (string)$this->params('category_catname')
        ]);
        $isOther = (bool)$this->params('other');

        if (!$currentCategory) {
            return $this->notFoundAction();
        }

        $categoryLang = $this->categoryLanguageTable->fetchRow([
            'language = ?'    => $language,
            'category_id = ?' => $currentCategory->id
        ]);

        $breadcrumbs = [[
            'name' => $categoryLang && $categoryLang->short_name ? $categoryLang->short_name : $currentCategory->name,
            'url'  => $this->url()->fromRoute('categories', [
                'action'           => 'category',
                'category_catname' => $currentCategory->catname,
                'other'            => false,
                'car_id'           => null,
                'path'             => [],
                'page'             => 1
            ])
        ]];

        $topCategory = $currentCategory;

        while ($parentCategory = $topCategory->findParentCategory()) {
            $topCategory = $parentCategory;

            $categoryLang = $this->categoryLanguageTable->fetchRow([
                'language = ?'    => $language,
                'category_id = ?' => $parentCategory->id
            ]);

            array_unshift($breadcrumbs, [
                'name' => $categoryLang && $categoryLang->short_name ? $categoryLang->short_name : $parentCategory->short_name,
                'url'  => $this->url()->fromRoute('categories', [
                    'action'           => 'category',
                    'category_catname' => $parentCategory->catname,
                    'other'            => false,
                    'car_id'           => null,
                    'path'             => [],
                    'page'             => 1
                ])
            ]);
        }

        $categoryLang = $this->categoryLanguageTable->fetchRow([
            'language = ?'    => $language,
            'category_id = ?' => $currentCategory->id
        ]);

        $carTable = $this->catalogue()->getCarTable();
        $carParentTable = new Car_Parent();

        $carId = $this->params('car_id');
        $topCar = false;
        $currentCar = false;
        if ($carId) {
            $topCar = $carTable->fetchRow(
                $carTable->select(true)
                    ->where('cars.id = ?', $carId)
                    ->join('car_parent_cache', 'cars.id = car_parent_cache.car_id', null)
                    ->join('category_car', 'car_parent_cache.parent_id = category_car.car_id', null)
                    ->join('category_parent', 'category_car.category_id = category_parent.category_id', null)
                    ->where('category_parent.parent_id = ?', $currentCategory->id)
            );
        }

        $path = [];

        if ($topCar) {
            $path = $this->params('path');
            $path = $path ? (array)$path : [];

            $breadcrumbs[] = [
                'name' => $topCar->getFullName($language),
                'url'  => $this->url()->fromRoute('categories', [
                    'action'           => 'category',
                    'category_catname' => $currentCategory->catname,
                    'other'            => false,
                    'car_id'           => $topCar->id,
                    'path'             => [],
                    'page'             => 1
                ])
            ];

            $currentCar = $topCar;

            $breadcrumbsPath = [];

            foreach ($path as $pathNode) {
                $childCar = $carTable->fetchRow(
                    $carTable->select(true)
                        ->join('car_parent', 'cars.id = car_parent.car_id', null)
                        ->where('car_parent.parent_id = ?', $currentCar->id)
                        ->where('car_parent.catname = ?', $pathNode)
                );

                if (!$childCar) {
                    return $this->notFoundAction();
                }

                $breadcrumbsPath[] = $pathNode;

                $breadcrumbs[] = [
                    'name' => $childCar->getFullName($language),
                    'url'  => $this->url()->fromRoute('categories', [
                        'action'           => 'category',
                        'category_catname' => $currentCategory->catname,
                        'other'            => false,
                        'car_id'           => $topCar->id,
                        'path'             => $breadcrumbsPath,
                        'page'             => 1
                    ])
                ];

                $currentCar = $childCar;
            }
        }

        $key = 'CATEGORY_MENU324_' . $topCategory->id . '_' . $language;

        $menu = $this->cache->getItem($key, $success);
        if (!$success) {

            $menu = $this->categoriesMenu($topCategory, $language, 3);

            $this->cache->setItem($key, $menu);
        }

        $this->categoriesMenuActive($menu, $currentCategory, $isOther);

        $data = [
            'category'     => $currentCategory,
            'categoryLang' => $categoryLang,
            'isOther'      => $isOther,
            'categories'   => $menu,
        ];

        $result =  $callback($language, $topCategory, $currentCategory,
            $categoryLang, $isOther, $topCar, $path, $currentCar, $breadcrumbs);

        if (is_array($result)) {
            return array_replace($data, $result);
        }

        return $result;
    }

    public function categoryAction()
    {
        return $this->_categoryAction(function($language, $topCategory,
                $currentCategory, $categoryLang, $isOther, $topCar, $path,
                $currentCar, $breadcrumbs) {

            $carTable = $this->catalogue()->getCarTable();

            if ($topCar) {
                $select = $carTable->select(true)
                    ->join('car_parent', 'cars.id = car_parent.car_id', null)
                    ->where('car_parent.parent_id = ?', $currentCar->id)
                    ->order($this->catalogue()->carsOrdering());

            } else {

                $select = $carTable->select(true)
                    ->join('category_car', 'cars.id = category_car.car_id', null)
                    ->order($this->catalogue()->carsOrdering());

                if ($isOther) {
                    $select->where('category_car.category_id=?', $currentCategory->id);
                } else {
                    $select
                        ->join('category_parent', 'category_car.category_id=category_parent.category_id', null)
                        ->group('cars.id')
                        ->where('category_parent.parent_id = ?', $currentCategory->id);
                }
            }

            $paginator = new \Zend\Paginator\Paginator(
                new Zend1DbTableSelect($select)
            );

            $paginator
                ->setItemCountPerPage($this->catalogue()->getCarsPerPage())
                ->setCurrentPageNumber($this->params('page'));

            $users = new Users();
            $contributors = $users->fetchAll(
                $users->select(true)
                    ->join('category_car', 'users.id = category_car.user_id', null)
                    ->join('category_parent', 'category_car.category_id = category_parent.category_id', null)
                    ->where('category_parent.parent_id = ?', $currentCategory->id)
                    ->where('not users.deleted')
                    ->group('users.id')
            );

            $title = '';
            if ($currentCategory) {
                if ($topCar) {
                    if ($currentCar) {
                        $title = $currentCar->getFullName($language);
                    } else {
                        $title = $topCar->getFullName($language);
                    }
                } else {
                    $title = $categoryLang ? $categoryLang->name : $currentCategory->name;
                }
            }

            $carParentTable = new Car_Parent();

            $listData = $this->car()->listData($paginator->getCurrentItems(), [
                'picturesDateSort' => true,
                'detailsUrl' => function($listCar) use ($topCar, $currentCar, $carParentTable, $currentCategory, $isOther, $path) {

                    $carParentAdapter = $carParentTable->getAdapter();
                    $hasChilds = (bool)$carParentAdapter->fetchOne(
                        $carParentAdapter->select()
                            ->from($carParentTable->info('name'), new Zend_Db_Expr('1'))
                            ->where('parent_id = ?', $listCar->id)
                    );

                    if (!$hasChilds) {
                        return false;
                    }

                    // found parent row
                    if ($currentCar) {
                        if (count($path)) {
                            $carParentRow = $carParentTable->fetchRow([
                                'car_id = ?'    => $listCar->id,
                                'parent_id = ?' => $currentCar->id
                            ]);
                            if ($carParentRow) {
                                $currentPath = array_merge($path, [
                                    $carParentRow->catname
                                ]);
                            } else {
                                $currentPath = false;
                            }
                        } else {
                            $carParentRow = $carParentTable->fetchRow([
                                'car_id = ?'    => $listCar->id,
                                'parent_id = ?' => $currentCar->id
                            ]);
                            if ($carParentRow) {
                                $currentPath = array_merge($path, [
                                    $carParentRow->catname
                                ]);
                            } else {
                                $currentPath = false;
                            }
                        }

                        if (!$currentPath) {
                            return false;
                        }
                    } else {
                        $currentPath = [];
                    }

                    $url = $this->url()->fromRoute('categories', [
                        'action'           => 'category',
                        'category_catname' => $currentCategory->catname,
                        'other'            => $isOther,
                        'car_id'           => $topCar ? $topCar->id : $listCar->id,
                        'path'             => $currentPath,
                        'page'             => 1
                    ]);

                    return $url;
                },
                'allPicturesUrl' => function($listCar) use ($topCar, $currentCar, $carParentTable, $currentCategory, $isOther, $path) {

                    // found parent row
                    if ($currentCar) {
                        if (count($path)) {
                            $carParentRow = $carParentTable->fetchRow([
                                'car_id = ?'    => $listCar->id,
                                'parent_id = ?' => $currentCar->id
                            ]);
                            if ($carParentRow) {
                                $currentPath = array_merge($path, [
                                    $carParentRow->catname
                                ]);
                            } else {
                                $currentPath = false;
                            }
                        } else {
                            $carParentRow = $carParentTable->fetchRow([
                                'car_id = ?'    => $listCar->id,
                                'parent_id = ?' => $currentCar->id
                            ]);
                            if ($carParentRow) {
                                $currentPath = array_merge($path, [
                                    $carParentRow->catname
                                ]);
                            } else {
                                $currentPath = false;
                            }
                        }

                        if (!$currentPath) {
                            return false;
                        }
                    } else {
                        $currentPath = [];
                    }

                    $url = $this->url()->fromRoute('categories', [
                        'action'           => 'category-pictures',
                        'category_catname' => $currentCategory->catname,
                        'other'            => $isOther,
                        'car_id'           => $topCar ? $topCar->id : $listCar->id,
                        'path'             => $currentPath,
                        'page'             => 1
                    ]);

                    return $url;
                },
                'pictureUrl'           => function($listCar, $picture) use ($currentCategory, $isOther, $topCar, $currentCar, $carParentTable, $path) {

                    // found parent row
                    if ($currentCar) {
                        if (count($path)) {
                            $carParentRow = $carParentTable->fetchRow([
                                'car_id = ?'    => $listCar->id,
                                'parent_id = ?' => $currentCar->id
                            ]);
                            if ($carParentRow) {
                                $currentPath = array_merge($path, [
                                    $carParentRow->catname
                                ]);
                            } else {
                                $currentPath = false;
                            }
                        } else {
                            $carParentRow = $carParentTable->fetchRow([
                                'car_id = ?'    => $listCar->id,
                                'parent_id = ?' => $currentCar->id
                            ]);
                            if ($carParentRow) {
                                $currentPath = array_merge($path, [
                                    $carParentRow->catname
                                ]);
                            } else {
                                $currentPath = false;
                            }
                        }

                        if (!$currentPath) {
                            return false;
                        }
                    } else {
                        $currentPath = [];
                    }

                    return $this->url()->fromRoute('categories', [
                        'action'           => 'category-picture',
                        'category_catname' => $currentCategory->catname,
                        'other'            => $isOther,
                        'car_id'           => $topCar ? $topCar->id : $listCar->id,
                        'path'             => $currentPath,
                        'picture_id'       => $picture['identity'] ? $picture['identity'] : $picture['id']
                    ]);
                }
            ]);

            return [
                'title'            => $title,
                'breadcrumbs'      => $breadcrumbs,
                'paginator'        => $paginator,
                'contributors'     => $contributors,
                'listData'         => $listData,
                'urlParams'        => [
                    'action'           => 'category',
                    'category_catname' => $currentCategory->catname,
                    'other'            => $isOther,
                    'car_id'           => $topCar ? $topCar->id : null,
                    'path'             => $path
                ]
            ];
        });
    }

    public function categoryPicturesAction()
    {
        return $this->_categoryAction(function($language, $topCategory,
                $currentCategory, $categoryLang, $isOther, $topCar, $path,
                $currentCar, $breadcrumbs) {

            $pictureTable = $this->catalogue()->getPictureTable();

            $select = $pictureTable->select(true)
                ->where('pictures.type = ?', Picture::CAR_TYPE_ID)
                ->where('pictures.status IN (?)', [
                    Picture::STATUS_NEW, Picture::STATUS_ACCEPTED
                ])
                ->order($this->catalogue()->picturesOrdering());

            if ($topCar) {
                $select
                    ->join('car_parent_cache', 'pictures.car_id = car_parent_cache.car_id', null)
                    ->where('car_parent_cache.parent_id = ?', $currentCar->id);

            } else {

                $select
                    ->join('car_parent_cache', 'pictures.car_id = car_parent_cache.car_id', null)
                    ->join('category_car', 'car_parent_cache.parent_id = category_car.car_id', null);

                if ($isOther) {
                    $select->where('category_car.category_id=?', $currentCategory->id);
                } else {
                    $select
                        ->join('category_parent', 'category_car.category_id = category_parent.category_id', null)
                        ->group('pictures.id')
                        ->where('category_parent.parent_id = ?', $currentCategory->id);
                }
            }

            $paginator = new \Zend\Paginator\Paginator(
                    new Zend1DbTableSelect($select)
            );

            $paginator
                ->setItemCountPerPage($this->catalogue()->getPicturesPerPage())
                ->setCurrentPageNumber($this->params('page'));

            $select->limitPage($paginator->getCurrentPageNumber(), $paginator->getItemCountPerPage());

            $picturesData = $this->pic()->listData($select, [
                'width' => 4,
                'url'   => function($picture) use ($currentCategory, $isOther, $topCar, $path) {
                    return $this->url()->fromRoute('categories', [
                        'action'           => 'category-picture',
                        'category_catname' => $currentCategory->catname,
                        'other'            => $isOther,
                        'car_id'           => $topCar ? $topCar->id : null,
                        'path'             => $path,
                        'picture_id'       => $picture['identity'] ? $picture['identity'] : $picture['id']
                    ]);
                }
            ]);

            return [
                'breadcrumbs'  => $breadcrumbs,
                'paginator'    => $paginator,
                'picturesData' => $picturesData,
            ];
        });
    }

    public function categoryPictureAction()
    {
        return $this->_categoryAction(function($language, $topCategory,
                $currentCategory, $categoryLang, $isOther, $topCar, $path,
                $currentCar, $breadcrumbs) {

            $pictureTable = $this->catalogue()->getPictureTable();

            $select = $pictureTable->select(true)
                ->where('pictures.type = ?', Picture::CAR_TYPE_ID)
                ->where('pictures.status IN (?)', [
                    Picture::STATUS_NEW, Picture::STATUS_ACCEPTED
                ])
                ->order($this->catalogue()->picturesOrdering());

            if ($topCar) {
                $select
                    ->join('car_parent_cache', 'pictures.car_id = car_parent_cache.car_id', null)
                    ->where('car_parent_cache.parent_id = ?', $currentCar->id);

            } else {

                $select
                    ->join('car_parent_cache', 'pictures.car_id = car_parent_cache.car_id', null)
                    ->join('category_car', 'car_parent_cache.parent_id = category_car.car_id', null);

                if ($isOther) {
                    $select->where('category_car.category_id = ?', $currentCategory->id);
                } else {
                    $select
                        ->join('category_parent', 'category_car.category_id = category_parent.category_id', null)
                        ->group('pictures.id')
                        ->where('category_parent.parent_id = ?', $currentCategory->id);
                }
            }

            $selectRow = clone $select;

            $pictureId = (string)$this->params('picture_id');

            $selectRow
                ->where('pictures.id = ?', $pictureId)
                ->where('pictures.identity IS NULL');

            $picture = $selectRow->getTable()->fetchRow($selectRow);

            if (!$picture) {
                $selectRow = clone $select;

                $selectRow->where('pictures.identity = ?', $pictureId);

                $picture = $selectRow->getTable()->fetchRow($selectRow);
            }

            if (!$picture) {
                return $this->notFoundAction();
            }

            return [
                'breadcrumbs' => $breadcrumbs,
                'picture'     => array_replace(
                    $this->pic()->picPageData($picture, $select, []),
                    [
                        'gallery2'   => true,
                        'galleryUrl' => $this->url()->fromRoute('categories', [
                            'action'           => 'category-picture-gallery',
                            'category_catname' => $currentCategory->catname,
                            'other'            => $isOther,
                            'car_id'           => $topCar ? $topCar->id : null,
                            'path'             => $path,
                            'picture_id'       => $picture['identity'] ? $picture['identity'] : $picture['id']
                        ])
                    ]
                )
            ];
        });
    }

    public function categoryPictureGalleryAction()
    {
        return $this->_categoryAction(function($language, $topCategory,
                $currentCategory, $categoryLang, $isOther, $topCar, $path,
                $currentCar, $breadcrumbs) {

            $pictureTable = $this->catalogue()->getPictureTable();

            $select = $pictureTable->select(true)
                ->where('pictures.type = ?', Picture::CAR_TYPE_ID)
                ->where('pictures.status IN (?)', [
                    Picture::STATUS_NEW, Picture::STATUS_ACCEPTED
                ])
                ->order($this->catalogue()->picturesOrdering());

            if ($topCar) {
                $select
                    ->join('car_parent_cache', 'pictures.car_id = car_parent_cache.car_id', null)
                    ->where('car_parent_cache.parent_id = ?', $currentCar->id);

            } else {

                $select
                    ->join('car_parent_cache', 'pictures.car_id = car_parent_cache.car_id', null)
                    ->join('category_car', 'car_parent_cache.parent_id = category_car.car_id', null);

                if ($isOther) {
                    $select->where('category_car.category_id=?', $currentCategory->id);
                } else {
                    $select
                        ->join('category_parent', 'category_car.category_id = category_parent.category_id', null)
                        ->group('pictures.id')
                        ->where('category_parent.parent_id = ?', $currentCategory->id);
                }
            }

            $selectRow = clone $select;

            $pictureId = (string)$this->params('picture_id');

            $selectRow
                ->where('pictures.id = ?', $pictureId)
                ->where('pictures.identity IS NULL');

            $picture = $selectRow->getTable()->fetchRow($selectRow);

            if (!$picture) {
                $selectRow = clone $select;

                $selectRow->where('pictures.identity = ?', $pictureId);

                $picture = $selectRow->getTable()->fetchRow($selectRow);
            }

            if (!$picture) {
                return $this->notFoundAction();
            }

            return new JsonModel($this->pic()->gallery2($select, [
                'page'      => $this->params('page'),
                'pictureId' => $this->params('pictureId'),
                'urlParams' => [
                    'action' => 'category-picture'
                ]
            ]));
        });
    }
}