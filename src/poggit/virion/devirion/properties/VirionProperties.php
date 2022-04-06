<?php

declare(strict_types=1);

namespace poggit\virion\devirion\properties;

final class VirionProperties{

	public string $name;

	public array $authors;

	public string $version;

	public string $antigen;

	public string $api;
}
//$name = $data["name"];
//		$authors = [];
//		if(isset($data["author"])){
//			$authors[] = $data["author"];
//		}
//		if(isset($data["authors"])){
//			$authors = array_merge($authors, (array) $data["authors"]);
//		}
//		if(!isset($data["version"])){
//			$this->getLogger()->error("Cannot load virion $name: Attribute 'version' missing in {$path}virion.yml");
//			return;
//		}
//		$virionVersion = $data["version"];
//		if(!isset($data["antigen"])){
//			$this->getLogger()->error("Cannot load virion $name: Attribute 'antigen' missing in {$path}virion.yml");
//			return;
//		}
//		if(isset($data["php"])){
//			foreach((array) $data["php"] as $php){
//				$parts = array_map("intval", array_pad(explode(".", (string) $php), 2, "0"));
//				if($parts[0] !== PHP_MAJOR_VERSION){
//					continue;
//				}
//				if($parts[1] <= PHP_MINOR_VERSION){
//					$ok = true;
//					break;
//				}
//			}
//			if(!isset($ok) and count((array) $data["php"]) > 0){
//				$this->getLogger()->error("Cannot load virion $name: Server is using incompatible PHP version " . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION);
//				return;
//			}
//		}
//		if(isset($data["api"]) && !ApiVersion::isCompatible($this->getServer()->getApiVersion(), (array) $data["api"])){
//			$this->getLogger()->error("Cannot load virion $name: Server has incompatible API version {$this->getServer()->getApiVersion()}");
//			return;
//
//		}
//
//		if(!isset($data["api"]) && !isset($data["php"])){
//			$this->getLogger()->error("Cannot load virion $name: Either 'api' or 'php' attribute must be declared in {$path}virion.yml");
//			return;
//		}
//
//		$antigen = $data["antigen"];