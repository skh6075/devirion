<?php

declare(strict_types=1);

/*
 * devirion
 *
 * Copyright (C) 2016 Poggit
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace poggit\virion\devirion;

use InvalidArgumentException;
use pocketmine\plugin\ApiVersion;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use poggit\virion\devirion\properties\VirionProperties;
use PrefixedLogger;
use RuntimeException;
use Webmozart\PathUtil\Path;
use function array_map;
use function array_pad;
use function count;
use function explode;
use function file_get_contents;
use function implode;
use function is_array;
use function is_dir;
use function mkdir;
use function yaml_parse;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;

class DEVirion extends PluginBase{

	private PrefixedLogger $virionLogger;

	private VirionClassLoader $classLoader;

	protected function onLoad() : void{
		$this->virionLogger = new PrefixedLogger($this->getServer()->getLogger(), $this->getName() . ": Logger");
		$this->classLoader = new VirionClassLoader();
		$virionPath = Path::join($this->getServer()->getDataPath(), "virions");
		if(!is_dir($virionPath) && !mkdir($virionPath) && !is_dir($virionPath)){
			throw new RuntimeException("Directory $virionPath was not created");
		}

		$resources = array_diff(scandir($virionPath), ['.', '..']);
		foreach($resources as $resource){
			$resourcePath = Path::join($virionPath, $resource);
			if(!is_dir($resourcePath)){
				continue;
			}
			$this->loadVirion($resourcePath);
		}

		if(count($this->classLoader->getKnownAntigens()) > 0){
			$this->virionLogger->warning("Virions should be bundled into plugins, not redistributed separately! Do NOT use DEVirion on production servers!!");
			$this->classLoader->register(true);
			$size = $this->getServer()->getAsyncPool()->getSize();
			for($i = 0; $i < $size; $i++){
				$this->getServer()->getAsyncPool()->submitTaskToWorker(new RegisterClassLoaderAsyncTask($this->classLoader), $i);
			}
		}
	}

	protected function onEnable() : void{
		if(count($this->classLoader->getKnownAntigens()) > 0){
			$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void{
				$messages = $this->classLoader->getMessages();
				while($messages->count() > 0){
					$this->virionLogger->warning($messages->shift());
				}
			}), 1);
		}
	}

	private function loadVirion(string $path) : void{
		$filePath = Path::join($path, "virion.yml");
		if(!file_exists($filePath)){
			throw new InvalidArgumentException("Cannot load virion: virion.yml missing. [Path: $filePath]");
		}
		$data = yaml_parse(file_get_contents($filePath));
		if(!is_array($data)){
			throw new InvalidArgumentException("Cannot load virion: Error parsing [Path: $filePath]");
		}
		if(!isset($data['name'], $data['version'], $data['antigen'], $data['api'])){
			throw new InvalidArgumentException("Cannot load virion: Required property value not available [Path: $filePath]");
		}
		$authors = (array)($data['authors'] ?? []);
		if(isset($data['author'])){
			$authors[] = $data['author'];
		}
		$properties = new VirionProperties;
		$properties->name = $data['name'];
		$properties->api = $data['api'];
		$properties->version = $data['version'];
		$properties->antigen = $data['antigen'];
		$properties->authors = $authors;
		if(isset($data['php'])){
			foreach((array)$data['php'] as $php){
				$parts = array_map("intval", array_pad(explode(".", (string) $php), 2, "0"));
				if($parts[0] !== PHP_MAJOR_VERSION){
					continue;
				}
				if($parts[1] <= PHP_MINOR_VERSION){
					$pass = true;
					break;
				}
				if(!isset($pass) && count((array)$data['php']) > 0){
					throw new InvalidArgumentException("Cannot load virion " . $properties->name . ": Server is using incompatible PHP version " . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION);
				}
			}
		}
		if(!ApiVersion::isCompatible($this->getServer()->getApiVersion(), (array)$properties->api)){
			throw new InvalidArgumentException("Cannot load virion " . $properties->name . ": Server has incompatible API version {$this->getServer()->getApiVersion()}");
		}
		$this->virionLogger->notice("Loading virion " . $properties->name . " v" . $properties->version . " by " . implode(", ", $authors) . " (antigen: " . $properties->antigen . ")");
		$this->classLoader->addAntigen($properties->antigen, Path::join($path, "src"));
	}
}
