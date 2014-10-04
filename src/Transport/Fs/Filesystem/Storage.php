<?php

namespace Jackalope\Transport\Fs\Filesystem;

use Jackalope\Transport\Fs\NodeSerializer\YamlNodeSerializer;
use Jackalope\Transport\Fs\Filesystem\PathRegistry;
use Jackalope\Transport\Fs\Filesystem\Storage\NodeWriter;
use Jackalope\Transport\Fs\Filesystem\Storage\StorageHelper;
use Jackalope\Transport\Fs\Filesystem\Storage\NodeReader;

class Storage
{
    const INDEX_DIR = '/indexes';
    const WORKSPACE_PATH = '/workspaces';
    const NAMESPACE_FILE = '/namespaces';
    const NS_DELIMITER = ':::';

    const IDX_REFERRERS_DIR = 'referrers';
    const IDX_WEAKREFERRERS_DIR = 'referrers-weak';
    const IDX_JCR_UUID = 'jcr-uuid';
    const IDX_INTERNAL_UUID = 'internal-uuid';
    const INTERNAL_UUID = 'jackalope:fs:id';

    private $filesystem;
    private $serializer;
    private $pathRegistry;
    private $nodeWriter;
    private $nodeReader;
    private $helper;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $serializer = new YamlNodeSerializer();
        $pathRegistry = new PathRegistry();
        $this->helper = new StorageHelper();

        $this->nodeWriter = new NodeWriter($this->filesystem, $serializer, $pathRegistry, $this->helper);
        $this->nodeReader = new NodeReader($this->filesystem, $serializer, $pathRegistry, $this->helper);
    }

    public function writeNode($workspace, $path, $nodeData)
    {
        $this->nodeWriter->writeNode($workspace, $path, $nodeData);
    }

    public function readNode($workspace, $path)
    {
        return $this->nodeReader->readNode($workspace, $path);
    }

    public function readNodesByUuids(array $uuids, $internal = false)
    {
        return $this->nodeReader->readNodesByUuids($uuids, $internal);
    }

    public function readBinaryStream($workspace, $path)
    {
        return $this->nodeReader->readBinaryStream($workspace, $path);
    }

    public function readNodeReferrers($workspace, $path, $weak = false, $name)
    {
        return $this->nodeReader->readNodeReferrers($workspace, $path, $weak, $name);
    }

    public function remove($path, $recursive = false)
    {
        $this->filesystem->remove($path, $recursive);
    }

    public function nodeExists($workspace, $path)
    {
        return $this->filesystem->exists($this->helper->getNodePath($workspace, $path));
    }

    public function workspaceExists($name)
    {
        return $this->filesystem->exists(self::WORKSPACE_PATH . '/' . $name);
    }

    public function workspaceRemove($name)
    {
        $this->filesystem->remove(self::WORKSPACE_PATH . '/' . $name);
    }

    public function workspaceList()
    {
        $list = $this->filesystem->ls(self::WORKSPACE_PATH);
        return $list['dirs'];
    }

    public function workspaceInit($name)
    {
        $this->writeNode($name, '/', array(
            'jcr:primaryType' => 'rep:root',
            ':jcr:primaryType' => 'Name',
        ));
    }

    public function ls($workspace, $path)
    {
        $fsPath = dirname($this->helper->getNodePath($workspace, $path));
        $list = $this->filesystem->ls($fsPath);

        return $list;
    }

    public function registerNamespace($workspaceName, $prefix, $uri)
    {
        $ns = $prefix . self::NS_DELIMITER . $uri;
        $out = array();
        if (false === $this->filesystem->exists(self::NAMESPACE_FILE)) {
            $out[] = $ns;
        } else {
            $out = explode("\n", $this->filesystem->read(self::NAMESPACE_FILE));
            $out[] = $ns;
        }

        $this->filesystem->write(self::NAMESPACE_FILE, implode("\n", $out));
    }

    public function unregisterNamespace($workspaceName, $targetPrefix)
    {
        $out = array();

        if (false === $this->filesystem->exists(self::NAMESPACE_FILE)) {
            return true;
        } else {
            $namespaces = explode("\n", $this->filesystem->read(self::NAMESPACE_FILE));
            foreach ($namespaces as $namespace) {
                list($prefix, $uri) = explode(self::NS_DELIMITER, $namespace);
                if ($prefix !== $targetPrefix) {
                    $out[] = $namespace;
                }
            }
        }

        $this->filesystem->write(self::NAMESPACE_FILE, implode("\n", $out));

        return true;
    }

    public function getNamespaces()
    {
        $res = array();

        if (!$this->filesystem->exists(self::NAMESPACE_FILE)) {
            return $res;
        }

        $namespaces = explode("\n", $this->filesystem->read(self::NAMESPACE_FILE));
        foreach ($namespaces as $namespace) {
            list($alias, $namespace) = explode(self::NS_DELIMITER, $namespace);
            $res[$alias] = $namespace;
        }

        return $res;
    }
}
