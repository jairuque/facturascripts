<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\App;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\MiniLogSave;
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Core\Base\TelemetryManager;
use FacturaScripts\Core\Base\ToolBox;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * App class is used for encapsulate common parts of code for the normal App execution.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class App
{

    /**
     * Database access manager.
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Plugin manager.
     *
     * @var PluginManager
     */
    protected $pluginManager;

    /**
     * Gives us access to the HTTP request parameters.
     *
     * @var Request
     */
    protected $request;

    /**
     * HTTP response object.
     *
     * @var Response
     */
    protected $response;

    /**
     * Requested Uri
     *
     * @var string
     */
    protected $uri;

    /**
     * Selects and runs the corresponding controller.
     *
     * @return bool
     */
    abstract public function run();

    /**
     * Initializes the app.
     *
     * @param string $uri
     */
    public function __construct(string $uri = '/')
    {
        $this->request = Request::createFromGlobals();
        if ($this->request->cookies->get('fsLang')) {
            $this->toolBox()->i18n()->setDefaultLang($this->request->cookies->get('fsLang'));
        }

        $this->dataBase = new DataBase();
        $this->pluginManager = new PluginManager();
        $this->response = new Response();
        $this->uri = $uri;

        /// timezone
        date_default_timezone_set(\FS_TIMEZONE);

        $this->toolBox()->log()->debug('URI: ' . $this->uri);
    }

    /**
     * Connects to the database and loads the configuration.
     *
     * @return bool
     */
    public function connect()
    {
        if ($this->dataBase->connect()) {
            $this->toolBox()->appSettings()->load();
            $this->loadPlugins();
            return true;
        }

        return false;
    }

    /**
     * Save log and disconnects from the database.
     *
     * @param string $nick
     */
    public function close(string $nick = '')
    {
        /// send telemetry (if configured)
        $telemetry = new TelemetryManager();
        $telemetry->update();

        /// save log
        new MiniLogSave($this->toolBox()->ipFilter()->getClientIp(), $nick, $this->uri);

        $this->dataBase->close();
    }

    /**
     * Returns the data into the standard output.
     */
    public function render()
    {
        $this->response->send();
    }

    /**
     * Returns param number $num in uri.
     *
     * @param int $num
     *
     * @return string
     */
    protected function getUriParam(string $num)
    {
        $params = explode('/', substr($this->uri, 1));
        return isset($params[$num]) ? $params[$num] : '';
    }

    /**
     * Add or increase the attempt counter of the current client IP address.
     */
    protected function ipWarning()
    {
        $ipFilter = $this->toolBox()->ipFilter();
        $ipFilter->setAttempt($ipFilter->getClientIp());
    }

    /**
     * Returns true if the client IP has been banned.
     *
     * @return bool
     */
    protected function isIPBanned()
    {
        $ipFilter = $this->toolBox()->ipFilter();
        if ($ipFilter->isBanned($ipFilter->getClientIp())) {
            $this->toolBox()->i18nLog()->critical('ip-banned');
            return true;
        }

        return false;
    }

    /**
     * Initialize plugins.
     */
    private function loadPlugins()
    {
        foreach ($this->pluginManager->enabledPlugins() as $pluginName) {
            $initClass = '\\FacturaScripts\\Plugins\\' . $pluginName . '\\Init';
            if (class_exists($initClass)) {
                $initObject = new $initClass();
                $initObject->init();
            }
        }
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }
}
