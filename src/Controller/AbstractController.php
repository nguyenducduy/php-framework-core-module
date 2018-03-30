<?php
namespace Core\Controller;

use Phalcon\Mvc\Controller as PhController;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

abstract class AbstractController extends PhController
{
    /**
     * Initializes the controller.
     *
     * @return void
     */
    public function initialize()
    {
        $di = $this->getDI();
    }

    public function onConstruct()
    {
        $this->transformer = $this->getDI()->get('transformer');
    }

    public function respondWithArray($array, $key)
    {
        $response = [$key => $array];

        return $this->onResponse($response);
    }

    public function respondWithOK()
    {
        $response = ['result' => 'OK'];

        return $this->onResponse($response);
    }

    public function respondWithFAIL()
    {
        $response = ['result' => 'FAIL'];

        return $this->onResponse($response);
    }

    public function createItemWithOK($item, $callback, $resource_key)
    {
        $response = $this->createItem($item, $callback, $resource_key);
        $response['result'] = 'OK';

        return $this->onResponse($response);
    }

    public function createItem($item, $callback, $resource_key, $meta = [])
    {
        $resource = new Item($item, $callback, $resource_key);
        $data = $this->transformer->createData($resource)->toArray();
        $response = array_merge($data, $meta);

        return $this->onResponse($response);
    }

    public function createCollection($collection, $callback, $resource_key, $meta = [])
    {
        $resource = new Collection($collection, $callback, $resource_key);
        $data = $this->transformer->createData($resource)->toArray();
        $response = array_merge($data, $meta);

        return $this->onResponse($response);
    }

    public function onResponse($response)
    {
        return $this->response->_send($response);
    }

    /**
     * get Current URL
     */
    public function getCurrentUrl()
    {
        return $this->router->getRewriteUri();
    }

    /**
     * get specified key from json POST
     */
    public function getParam($key = '')
    {
        $formData = (array) json_decode($this->request->getRawBody());

        if (strlen($key) > 0 && array_key_exists($key, $formData)) {
            return $formData[$key];
        } else {
            return $formData;
        }
    }
}
