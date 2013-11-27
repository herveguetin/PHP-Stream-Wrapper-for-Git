<?php
/*
 * Copyright (C) 2011 by TEQneers GmbH & Co. KG
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Git Stream Wrapper for PHP
 *
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

/**
 * @namespace
 */
namespace TQ\Git\StreamWrapper;
use TQ\Git\Repository\Repository;
use TQ\Git\Cli\Binary;

/**
 * Handles decomposition of a given Git stream wrapper path
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage StreamWrapper
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class PathInformation
{
    /**
     * The host name used for global paths
     */
    const GLOBAL_PATH_HOST  = '__global__';

    /**
     * The Git repository
     *
     * @var Repository
     */
    protected $repository;

    /**
     * The Git URL
     *
     * @var string
     */
    protected $url;

    /**
     * The absolute path to the resource
     *
     * @var string
     */
    protected $fullPath;

    /**
     * The relative path to the resource based on the repository path
     *
     * @var string
     */
    protected $localPath;

    /**
     * The version ref
     *
     * @var string
     */
    protected $ref;

    /**
     * Additional arguments
     *
     * @var array
     */
    protected $arguments;

    /**
     * Returns path information for a given stream path
     *
     * @param   string      $path       The path
     * @param   string      $protocol   The protocol registered
     * @return  array                   An array containing information about the path
     */
    public static function parsePath($path, $protocol)
    {
        // normalize directory separators
        $path   = str_replace(array('\\', '/'), '/', $path);
        //fix path if fragment has been munged into the path (e.g. when using the RecursiveIterator)
        $path   = preg_replace('~^(.+?)(#[^/]+)(.*)$~', '$1$3$2', $path);

        /// fix /// paths to __global__ "host"
        if (strpos($path, $protocol.':///') === 0) {
            $path   = str_replace($protocol.':///', $protocol.'://'.self::GLOBAL_PATH_HOST.'/', $path);
        }

        $info   = parse_url($path);
        if (isset($info['path']) && preg_match('~^/\w:.+~', $info['path'])) {
            $info['path']   = ltrim($info['path'], '/');
        }
        return $info;
    }

    /**
     * Creates a new path information instance from a given URL
     *
     * @param   string      $url        The URL
     * @param   string      $protocol   The protocol registered
     * @param   Binary      $binary     The Git binary
     */
    public function __construct($url, $protocol, Binary $binary)
    {
        $url                = self::parsePath($url, $protocol);
        $this->fullPath     = $url['path'];
        $this->repository   = Repository::open($this->fullPath, $binary, false);
        $this->localPath    = $this->repository->resolveLocalPath($this->fullPath);
        $this->ref          = isset($url['fragment']) ? $url['fragment'] : 'HEAD';

        $arguments  = array();
        if (isset($url['query'])) {
            parse_str($url['query'], $arguments);
        }
        $this->arguments    = $arguments;

        $this->url          =  $protocol.'://'.$this->fullPath
                              .'#'.$this->ref
                              .'?'.http_build_query($this->arguments);
    }

    /**
     * Returns the Git URL
     *
     * @return  string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the Git repository instance
     *
     * @return  Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Returns the absolute repository path
     *
     * @return  string
     */
    public function getRepositoryPath()
    {
        return $this->getRepository()->getRepositoryPath();
    }

    /**
     * Returns the absolute path to the resource
     *
     * @return  string
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }

    /**
     * Returns the relative path to the resource based on the repository path
     *
     * @return  string
     */
    public function getLocalPath()
    {
        return $this->localPath;
    }

    /**
     * Returns the version ref
     *
     * @return  string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Returns the additional arguments given
     *
     * @return  array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Checks if the given argument exists
     *
     * @param   string  $argument   The argument name
     * @return  boolean
     */
    public function hasArgument($argument)
    {
        return array_key_exists($argument, $this->arguments);
    }

    /**
     * Returns the given argument from the argument collection
     *
     * @param   string  $argument   The argument name
     * @return  string|null         The argument value or NULL if the argument does not exist
     */
    public function getArgument($argument)
    {
        return ($this->hasArgument($argument)) ? $this->arguments[$argument] : null;
    }
}