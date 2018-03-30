<?php
namespace Core\Model;

use Phalcon\DI;
use Phalcon\Mvc\Model as PhModel;
use Phalcon\Mvc\Model\Query\Builder as PhBuilder;

abstract class AbstractModel extends PhModel
{
    /**
     * Paginator.
     * @param  [array] $params Condition query
     * @param  [integer] $limit  Limit page
     * @param  [integer] $offset Offset page
     * @return [object] Paginator object
     */
    public static function paginate($formData, $limit, $offset, $cache = false, $lifetime = 0)
    {
        $model = get_called_class();
        $whereString = '';
        $bindParams = [];
        $bindTypeParams = [];

        if (is_array($formData['conditions'])) {
            if (isset($formData['conditions']['keyword'])
                && strlen($formData['conditions']['keyword']) > 0
                && isset($formData['conditions']['searchKeywordIn'])
                && count($formData['conditions']['searchKeywordIn']) > 0) {
                /**
                 * Search keyword
                 */
                $searchKeyword = $formData['conditions']['keyword'];
                $searchKeywordIn = $formData['conditions']['searchKeywordIn'];

                $whereString .= $whereString != '' ? ' OR ' : ' (';

                $sp = '';
                foreach ($searchKeywordIn as $searchIn) {
                    $sp .= ($sp != '' ? ' OR ' : '') . $searchIn . ' LIKE :searchKeyword:';
                }

                $whereString .= $sp . ')';
                $bindParams['searchKeyword'] = '%' . $searchKeyword . '%';
            }

            /**
             * Optional Filter by tags
             */
            if (count($formData['conditions']['filterBy']) > 0) {
                $filterby = $formData['conditions']['filterBy'];

                foreach ($filterby as $k => $v) {
                    if ($v) {
                        // Compare character
                        $compareChar = '=';
                        $firstChar = substr($k, 0, 1);
                        if ($firstChar == '!') {
                            if ($v == -1) {
                                $v = 0;
                            }
                            $compareChar = '!=';
                            $k = str_replace($firstChar, '', $k);
                        }

                        switch (gettype($v)) {
                            case 'string':
                                $bindTypeParams[$k] =  \PDO::PARAM_STR;
                                break;

                            default:
                                $bindTypeParams[$k] = \PDO::PARAM_INT;
                                break;
                        }

                        // NOT IN ..
                        switch ($k) {
                            case 'NOT IN#id':
                                $whereString .= ($whereString != '' ? ' AND ' : '') . 'id ' . str_replace('#id', '', $k) . ' (:NOTIN:)';
                                $bindParams['NOTIN'] = $v;
                                $bindTypeParams['NOTIN'] =  \PDO::PARAM_STR;
                                unset($bindTypeParams[$k]);
                                break;

                            default:
                                $whereString .= ($whereString != '' ? ' AND ' : '') . $k . ' '. $compareChar .' :' . $k . ':';
                                $bindParams[$k] = $v;
                                break;
                        }
                    }
                }
            }

            if (strlen($whereString) > 0 && count($bindParams) > 0) {
                $formData['conditions'] = [
                    [
                        $whereString,
                        $bindParams,
                        $bindTypeParams
                    ]
                ];
            } else {
                $formData['conditions'] = '';
            }
        }

        $params = [
            'models' => $model,
            'columns' => $formData['columns'],
            'conditions' => $formData['conditions'],
            'order' => [$model . '.' . $formData['orderBy'] .' '. $formData['orderType']]
        ];

        $builder = new PhBuilder($params);
        $paginatorKey = 'builder';

        if ($cache) {
            // Cache key
            $key = 'model.paginate.' . self::_createKey($params);

            // Check cache
            $cacheService = self::getStaticDI()->get('cacheData');
            $simpleResultSet = $cacheService->get($key);

            if ($simpleResultSet) {
                $model = '\Phalcon\Paginator\Adapter\Model';
                $builder = $simpleResultSet;
                $paginatorKey = 'data';
            } else {
                $model = '\Phalcon\Paginator\Adapter\QueryBuilder';

                // Set cache
                $builder->getQuery()->cache([
                    'key' => $key,
                    'lifetime' => $lifetime // seconds, 5 minutes
                ])->execute();
            }
        } else {
            $model = '\Phalcon\Paginator\Adapter\QueryBuilder';
        }

        // Create paginator object
        $paginator = new $model([
            $paginatorKey => $builder,
            'limit' => $limit,
            'page' => $offset
        ]);

        return $paginator->getPaginate();
    }

    /**
     * Returns the DI container
     */
    public function getDI()
    {
        return DI\FactoryDefault::getDefault();
    }

    /**
     * Returns the static DI container
     */
    public static function getStaticDI()
    {
        return DI\FactoryDefault::getDefault();
    }

    // Override findFirst function to create cache
    public static function findFirst($parameters = null)
    {
        if (isset($parameters['cache'])) {
            // Cache key
            $key = 'model.first.' . self::_createKey($parameters);

            $parameters['cache'] = [
                'key' => $key,
                'lifetime' => $parameters['cache']['lifetime'],
            ];
        }

        return parent::findFirst($parameters);
    }

    // Override find function to create cache
    public static function find($parameters = null)
    {
        if (isset($parameters['cache'])) {
            // Cache key
            $key = 'model.find.' . self::_createKey($parameters);

            $parameters['cache'] = [
                'key' => $key,
                'lifetime' => $parameters['cache']['lifetime'],
            ];
        }

        return parent::find($parameters);
    }

    // Override count function to create cache
    public static function count($parameters = null)
    {
        if (isset($parameters['cache'])) {
            // Cache key
            $key = 'model.count.' . self::_createKey($parameters);

            $parameters['cache'] = [
                'key' => $key,
                'lifetime' => $parameters['cache']['lifetime'],
            ];
        }

        return parent::count($parameters);
    }

    public static function _createKey($params): string
    {
        // Cache key
        $key = str_replace('\\', '', get_called_class()) . '.' . md5(json_encode($params)) . '.cache';

        return $key;
    }
}
