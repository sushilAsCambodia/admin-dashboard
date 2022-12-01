<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

if (! function_exists('getErrorMessages')) {
    function getErrorMessages($messages)
    {
        $errorMessages = [];
        foreach ($messages as $key => $values) {
            foreach ($values as $index => $value) {
                array_push($errorMessages, $value);
            }
        }

        return $errorMessages;
    }
}

if (! function_exists('generalErrorResponse')) {
    function generalErrorResponse(Exception $e)
    {
        return response()->json([
            'messages' => [$e->getMessage()],
            'trace' => [$e->getTrace()],
        ], 400);
    }
}

if (! function_exists('paginate')) {
    function paginate($items, $perPage = 100, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
}

if (! function_exists('zeroappend')) {
    function zeroappend($LastNumber)
    {
        $count = (int) log10(abs($LastNumber)) + 1;
        if ($count == 1) {
            return $append = '000000';
        } elseif ($count == 2) {
            return $append = '00000';
        } elseif ($count == 3) {
            return $append = '0000';
        } elseif ($count == 4) {
            return $append = '000';
        } elseif ($count == 5) {
            return $append = '00';
        } elseif ($count == 6) {
            return $append = '0';
        } elseif ($count == 7) {
            return $append = '';
        } else {
            return $append = '';
        }
    }
}

if (! function_exists('isMechantUser')) {
    function isMechantUser()
    {
        if (! Auth::user()) {
            return false;
        }

        return in_array('merchant', Auth::user()->roles->pluck('name')->toArray());
    }
}
