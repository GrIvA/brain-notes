<?php

namespace App\Abstract;

use Analog\Logger;
use App\Helpers\Censor;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractController
{
    const HANDLING_STATUS_ABSTRACT_OK = 1;
    const HANDLING_STATUS_ERROR_PARAMS_CHECK = 100;

    protected $container = null;
    protected $settings = null;
    protected $params = [];
    protected $pageId = null;

    public $dbase = null;  /* @var Medoo|null */
    public $log = null; /* @var Logger|null */

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->settings = $container->get('settings');
        $this->dbase = $container->get('dbase');
        $this->log = $container->get(Logger::class);
    }

    /*
     * Check and verify input parameters.
     * The every key of input array is the name of paramater
     * and the value of the key it is name of the Censor's regexp
     * @input $data []
     * @return []
     */
    public function verifyInput(array $data)
    {
        $result = ['status' => self::HANDLING_STATUS_ABSTRACT_OK];
        foreach ($data as $name => $rule) {
            //code
            if (!isset($this->params[$name])) {
                $result = [
                    'status' => self::HANDLING_STATUS_ERROR_PARAMS_CHECK,
                    'error_code' => 100,
                    'error_desc' => 'Not found parameter '.$name,
                    'error_field' => $name,
                    'error_rule' => $rule
                ];
                break;
            }
            if (!Censor::has($rule)) {
                $result = [
                    'status' => self::HANDLING_STATUS_ERROR_PARAMS_CHECK,
                    'error_code' => 200,
                    'error_desc' => 'Not expression '. $rule,
                    'error_field' => $name,
                    'error_rule' => $rule
                ];
                break;
            }
            if (!Censor::is($this->params[$name], $rule)) {
                $result = [
                    'status' => self::HANDLING_STATUS_ERROR_PARAMS_CHECK,
                    'error_code' => 300,
                    'error_desc' => 'Not correct '.$name.' "'. $this->params[$name] . '", used rule: ' . $rule,
                    'error_field' => $name,
                    'error_rule' => $rule
                ];
                break;
            }
        }
        return $result;
    }

    protected function parseArgs(ServerRequestInterface $req, array $args, bool $use_escape = true)
    {
        $body_params = $req->getParsedBody();
        $query_params = $req->getQueryParams() + (is_null($body_params) ? [] : $body_params);
        if (is_array($query_params) && count($query_params) > 1) {
            unset($query_params['url']);
            $args = $args + $query_params;
        }

        if ($use_escape && count($args)) {
            array_walk_recursive(
                $args,
                function (&$value) {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            );
        }

        $this->params = $args;
    }

    /**
     * @param array|string $error
     * @param string $logLevel
     */
    protected function errorLog($error, $logLevel = 'warning')
    {
        ob_start();
        debug_print_backtrace(0, 1);
        if (!method_exists($this->log, $logLevel)) {
            $logLevel = 'warning';
        }

        $this->log->$logLevel(
            'Error: '.(is_array($error) ? print_r($error, true) : $error).' Trace: '. ob_get_clean()
        );
    }
}
