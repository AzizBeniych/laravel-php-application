<?php

// Debugging all SQL executed
try {
	DB::listen(function ($sql, $bindings/*, $time*/) {
		Log::debug($sql . PHP_EOL . \Illuminate\Support\Str::limit(print_r($bindings, true), 8192));
	});
} catch (\Exception $e) {
	Log::warning($e->getMessage());
}
