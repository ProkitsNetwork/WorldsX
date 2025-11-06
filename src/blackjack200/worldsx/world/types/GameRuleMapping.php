<?php

namespace blackjack200\worldsx\world\types;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\FloatGameRule;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\IntGameRule;

class GameRuleMapping {
	private static array $externalToInternal;
	private static array $internalToExternal;
	private static array $internalSchema;

	public static function setup(array $nameMapping, array $gameRuleMap) : void {
		self::$externalToInternal = [];
		self::$internalToExternal = [];
		foreach ($nameMapping as $external => $internal) {
			self::$externalToInternal[mb_strtolower($external)] = $internal;
			self::$externalToInternal[mb_strtolower($internal)] = $internal;
			self::$internalToExternal[mb_strtolower($internal)] = $external;
		}
		self::$internalSchema = $gameRuleMap;
	}

	public static function toInternal(string $external) : ?string {
		return self::$externalToInternal[mb_strtolower($external)] ?? null;
	}

	public static function toExternal(string $internal) : ?string {
		return self::$internalToExternal[mb_strtolower($internal)] ?? null;
	}

	public static function create(string $internal, $val) : ?GameRule {
		$schema = self::$internalSchema[$internal] ?? null;
		if ($schema === null) {
			return null;
		}
		/** @var class-string<BoolGameRule,IntGameRule,FloatGameRule> $class */
		$class = match ($schema['type']) {
			'bool' => BoolGameRule::class,
			'int' => IntGameRule::class,
			'float' => FloatGameRule::class,
		};
		$v = self::convertVal($internal, $val);
		if ($v !== $schema['default']) {
			return new $class($v, false);
		}
		return null;
	}

	public static function convertVal(string $internal, $val) {
		$schema = self::$internalSchema[$internal] ?? null;
		if ($schema === null) {
			return $val;
		}
		return match ($schema['type']) {
			'bool' => is_bool($val) ? $val : strtolower($val) === 'true',
			'int' => (int) $val,
			'float' => (float) $val,
			default => $val,
		};
	}

	public static function getDefaultTags() : CompoundTag {
		$t = new CompoundTag();
		foreach (self::$internalSchema as $internal => $schema) {
			$t->setString($internal, json_encode($schema['default']));
		}
		return $t;
	}

	public static function default(string $internal) {
		return self::convertVal($internal, self::$internalSchema[$internal]['default']);
	}

	public static function getAllInternal() : array {
		return array_values(self::$externalToInternal);
	}
}