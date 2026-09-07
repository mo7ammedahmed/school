<?php
error_reporting(error_reporting() & ~(E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING | E_DEPRECATED | E_USER_DEPRECATED));
class LspHelper
{
public static function relativePath($path)
{
if (!str_contains($path, base_path())) {
return (string) $path;
}

return ltrim(str_replace(base_path(), '', realpath($path) ?: $path), DIRECTORY_SEPARATOR);
}

public static function isVendor($path)
{
return str_contains($path, base_path('vendor'));
}

public static function propertyDefault(ReflectionProperty $property, ?ReflectionParameter $parameter = null): array
{
if ($property->hasDefaultValue()) {
return ['default' => $property->getDefaultValue()];
}

if ($parameter?->isDefaultValueAvailable()) {
return ['default' => $parameter->getDefaultValue()];
}

return [];
}

public static function formatDefaultValue(mixed $value): mixed
{
return match (true) {
is_array($value) => 'array(...)',
$value instanceof UnitEnum => get_class($value) . '::' . $value->name,
$value instanceof Closure => 'Closure',
is_object($value) => get_class($value),
is_string($value) => var_export($value, true),
is_null($value) => 'null',
is_bool($value) => $value ? 'true' : 'false',
default => $value,
};
}
}

function getReflectionMethod(ReflectionClass $reflected): ReflectionMethod
{
return match (true) {
$reflected->hasMethod('__invoke') => $reflected->getMethod('__invoke'),
default => $reflected->getMethod('handle'),
};
}

function getMethodLine(ReflectionClass $reflected, ReflectionMethod $method): ?int
{
return $method->getFileName() === $reflected->getFileName()
? $method->getStartLine()
: null;
}

echo collect(app("Illuminate\Contracts\Http\Kernel")->getMiddlewareGroups())
->merge(app("Illuminate\Contracts\Http\Kernel")->getRouteMiddleware())
->merge(app('router')->getMiddleware())
->map(function ($middleware, $key) {
$result = [
'class' => null,
'path' => null,
'line' => null,
'parameters' => null,
'groups' => [],
];

if (is_array($middleware)) {
$result['groups'] = collect($middleware)->map(function ($m) {
if (!class_exists($m)) {
return [
'class' => $m,
'path' => null,
'line' => null,
];
}

$reflected = new ReflectionClass($m);
$reflectedMethod = getReflectionMethod($reflected);

return [
'class' => $m,
'path' => LspHelper::relativePath($reflected->getFileName()),
'line' => getMethodLine($reflected, $reflectedMethod),
];
})->all();

return $result;
}

$reflected = new ReflectionClass($middleware);
$reflectedMethod = getReflectionMethod($reflected);

$result = array_merge($result, [
'class' => $middleware,
'path' => LspHelper::relativePath($reflected->getFileName()),
'line' => getMethodLine($reflected, $reflectedMethod),
]);

$parameters = collect($reflectedMethod->getParameters())
->filter(function ($rc) {
return $rc->getName() !== 'request' && $rc->getName() !== 'next';
})
->map(function ($rc) {
return $rc->getName() . ($rc->isVariadic() ? '...' : '');
});

if ($parameters->isEmpty()) {
return $result;
}

return array_merge($result, [
'parameters' => $parameters->implode(','),
]);
})
->toJson();
