<?php

class Cars extends Project_Db_Table
{
    protected $_name = 'cars';
    protected $_use_selects_cache = true;
    protected $_rowClass = 'Cars_Row';
    protected $_rowsetClass = 'Cars_Rowset';
    protected $_referenceMap = array(
        'Type' => array(
            'columns'       => array('car_type_id'),
            'refTableClass' => 'Car_Types',
            'refColumns'    => array('id')
        ),
        'Meta_Last_Editor' => array(
            'columns'       => array('meta_last_editor_id'),
            'refTableClass' => 'Users',
            'refColumns'    => array('id')
        ),
        'Tech_Last_Editor' => array(
            'columns'       => array('tech_last_editor_id'),
            'refTableClass' => 'Users',
            'refColumns'    => array('id')
        ),
        'Engine' => array(
            'columns'       => array('engine_id'),
            'refTableClass' => 'Engines',
            'refColumns'    => array('id')
        )
    );


    public function insert(array $data)
    {
        if (isset($data['body'])) {
            $data['body'] = trim($data['body']);
        }
        $data['add_datetime'] = date('Y-m-d H:i:s');

        return parent::insert($data);
    }

    public function updateInteritance(Cars_Row $car)
    {
        $parents = $this->fetchAll(
            $this->select(true)
                ->join('car_parent', 'cars.id = car_parent.parent_id', null)
                ->where('car_parent.car_id = ?', $car->id)
        );

        $somethingChanged = false;

        if ($car->is_concept_inherit) {
            $isConcept = false;
            foreach ($parents as $parent) {
                if ($parent->is_concept) {
                    $isConcept = true;
                }
            }

            $oldIsConcept = (bool)$car->is_concept;

            if ($oldIsConcept !== $isConcept) {
                $car->is_concept = $isConcept;
                $somethingChanged = true;
            }
        }

        if ($car->engine_inherit) {
            $map = array();
            foreach ($parents as $parent) {
                $engineId = $parent->engine_id;
                if ($engineId) {
                    if (isset($map[$engineId])) {
                        $map[$engineId]++;
                    } else {
                        $map[$engineId] = 1;
                    }
                }
            }

            // select top
            $maxCount = null;
            $selectedId = null;
            foreach ($map as $id => $count) {
                if (is_null($maxCount) || ($count > $maxCount)) {
                    $maxCount = $count;
                    $selectedId = (int)$id;
                }
            }

            $oldEngineId = isset($car->engine_id) ? (int)$car->engine_id : null;

            if ($oldEngineId !== $selectedId) {
                $car->engine_id = $selectedId;
                $somethingChanged = true;
            }
        }

        if ($car->car_type_inherit) {
            $map = array();
            foreach ($parents as $parent) {
                $typeId = $parent->car_type_id;
                if ($typeId) {
                    if (isset($map[$typeId])) {
                        $map[$typeId]++;
                    } else {
                        $map[$typeId] = 1;
                    }
                }
            }

            $carTypeParentTable = new Car_Types_Parents();
            $carTypeParentTableName = $carTypeParentTable->info('name');
            $db = $carTypeParentTable->getAdapter();
            foreach ($map as $id => $count) {

                $otherIds = array_diff(array_keys($map), array($id));

                if (count($otherIds)) {
                    $isParentOf = $db->fetchCol(
                        $db->select()
                            ->from($carTypeParentTableName, 'id')
                            ->where('id in (?)', $otherIds)
                            ->where('parent_id = ?', $id)
                            ->where('id <> parent_id')
                    );

                    if (count($isParentOf)) {
                        foreach ($isParentOf as $childId) {
                            $map[$childId] += $count;
                        }

                        unset($map[$id]);
                    }
                }
            }

            // select top
            $maxCount = null;
            $selectedId = null;
            foreach ($map as $id => $count) {
                if (is_null($maxCount) || ($count > $maxCount)) {
                    $maxCount = $count;
                    $selectedId = (int)$id;
                }
            }

            $oldCarTypeId = isset($car->car_type_id) ? (int)$car->car_type_id : null;

            if ($oldCarTypeId !== $selectedId) {
                $car->car_type_id = $selectedId;
                $somethingChanged = true;
            }
        }

        if ($car->spec_inherit) {
            $map = array();
            foreach ($parents as $parent) {
                $specId = $parent->spec_id;
                if ($specId) {
                    if (isset($map[$specId])) {
                        $map[$specId]++;
                    } else {
                        $map[$specId] = 1;
                    }
                }
            }

            // select top
            $maxCount = null;
            $selectedId = null;
            foreach ($map as $id => $count) {
                if (is_null($maxCount) || ($count > $maxCount)) {
                    $maxCount = $count;
                    $selectedId = (int)$id;
                }
            }

            $oldSpecId = isset($car->spec_id) ? (int)$car->spec_id : null;

            if ($oldSpecId !== $selectedId) {
                $car->spec_id = $selectedId;
                $somethingChanged = true;
            }
        }

        if ($somethingChanged || !$car->car_type_inherit) {
            $car->save();

            $childs = $this->fetchAll(
                $this->select(true)
                    ->join('car_parent', 'cars.id = car_parent.car_id', null)
                    ->where('car_parent.parent_id = ?', $car->id)
            );

            foreach ($childs as $child) {
                $this->updateInteritance($child);
            }
        }
    }
}