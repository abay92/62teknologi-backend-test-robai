<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

trait FilterQuery
{
    public static $searchable = [];

    public static $filterable = [];

    /**
     * Query scope
     *
     * @param array $data - [id, select, searchField, join]
     * @param Request $request
     * @return Eloquent
     */

    public static function onQuery(Request $request, array $data)
    {
        $dataSelect = $data['select'];

        $select = self::changeSelect($dataSelect);
        $join   = Arr::get($data, 'join', []);

        self::$searchable = $data['searchField'];
        self::$filterable = $dataSelect;

        $selectRaw = [];

        foreach ($select as $k => $v) :
            $selectRaw[] = $k . ' as ' . $v;
        endforeach;

        $table = with(new static())->getTable();
        if (isset($data['isBuilder']) && $data['isBuilder']) {
            $query = DB::table($table)->selectRaw(join(', ', $selectRaw));
        } else {
            $query = static::selectRaw(join(', ', $selectRaw));
        }

        $query = self::onJoin($query, $join);

        $query = self::onSearch($query, $request);

        $query = self::onSort($query, $table, $request);

        $query = self::onFilter($query, $request);

        if (isset($data['softDelete']) && $data['softDelete']) {
            $query = $query->whereNull($table . '.deleted_at');
        }

        return $query;
    }

    /**
     * Select raw scope
     *
     * @param $query
     * @param array $selectRaw
     * @return Eloquent
     */

    public static function onJoin($query, array $joins)
    {
        if ($joins) :
            foreach ($joins as $table => $option) :
                // join with table alias
                if (isset($option['alias'])) :
                    $option['pk'] = str_replace($option['alias'], $table, $option['pk']);
                    $table = $option['alias'] . ' as ' . $table;
                endif;

                // join with subquery raw
                if (isset($option['aliasRaw'])) :
                    $option['pk'] = str_replace($option['aliasRaw'], $table, $option['pk']);
                    $table = DB::raw('(' . $option['aliasRaw'] . ') as ' . $table);
                endif;

                switch ($option['type']) :
                    case 'join':
                        $query->join($table, $option['pk'], '=', $option['fk']);
                        break;

                    case 'leftJoin':
                        $query->leftJoin($table, $option['pk'], '=', $option['fk']);
                        break;
                endswitch;
            endforeach;
        endif;

        return $query;
    }

    /**
     * Search scope
     *
     * @param $query
     * @param Request $request
     * @return Eloquent
     */

    public static function onSearch($query, Request $request)
    {
        $searchTerm = $request->search;
        if (!is_null($searchTerm)) :
            $query->where(function ($query) use ($searchTerm) {
                foreach (self::$searchable as $column) :
                    $query->orWhere($column, 'like', '%' . $searchTerm . '%');
                endforeach;
            });
        endif;

        return $query;
    }

    /**
     * Sort scope
     *
     * @param $query
     * @param Request $request
     * @return Eloquent
     */

    public static function onSort($query, $table, Request $request)
    {
        $sort_column = $request->sort_column;
        $sort_type   = $request->sort_type;

        if (!is_null($sort_column) && !is_null($sort_type)) :
            $query->orderBy($sort_column, $sort_type);
        else :
            $query->orderBy($table . '.created_at', 'desc');
        endif;

        return $query;
    }

    /**
     * Filter scope
     *
     * @param $query
     * @param Request $request
     * @return Eloquent
     */

    public static function onFilter($query, Request $request)
    {
        $reqFilter  = $request->input('filter', []);

        if ($reqFilter) :
            $filterable = array_flip(self::$filterable);

            $query->where(function ($query) use ($reqFilter, $filterable) {
                foreach ($reqFilter as $key => $value) :
                    $query->whereIn(DB::raw($filterable[$key]), explode(';', $value));
                endforeach;
            });
        endif;

        return $query;
    }

    /**
     * Paginate scope
     *
     * @param $query
     * @param Request $request
     * @return Eloquent
     */

    public static function onPaginate($query, Request $request)
    {
        if ($request->limit == 'all') :
            $perPage = $query->count();
        else :
            $perPage = (int) $request->limit;
            $perPage = $perPage ? $perPage : 10;
        endif;

        return $query->paginate($perPage);
    }

    public static function changeSelect(array $select)
    {
        return collect($select)->transform(function ($item) {
            return in_array($item, ['key', 'order', 'limit']) ? "`{$item}`" : $item;
        })->toArray();
    }
}
