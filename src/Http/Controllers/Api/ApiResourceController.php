<?php namespace Falcomnl\LaravelApiController\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Exceptions\InvalidAppendQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;
use Spatie\QueryBuilder\Exceptions\InvalidFilterQuery;
use Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery;
use Spatie\QueryBuilder\Exceptions\InvalidSortQuery;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Class ApiResourceController
 * @package App\Http\Controllers\Api
 * @ todo $appends vs $allowed_appends
 */
class ApiResourceController extends ApiController {

    protected $model;
    protected $use_authorization = true;
    protected $allowed_operations = [];

    protected $list_fields      = ['*'];
    protected $detail_fields    = ['*'];

    protected $allowed_fields   = [];
    protected $allowed_filters  = [];
    protected $allowed_sorts    = '';
    protected $default_sort     = '';
    protected $allowed_includes = [];
    protected $allowed_appends  = [];
    protected $paginate         = true;

    protected $constraints      = [];

    public function __construct(Request $request)
    {
        if($this->use_authorization)
            $this->authorizeResource($this->model);

        if(env('LOG_API'))  $this->_log();
    }

    // region crud

    public function index(Request $request): JsonResponse
    {
        if(!$this->_isAllowed('index')) return $this->responseNotImplemented();

        $query = QueryBuilder::for($this->model);

        if($this->list_fields != '*')
            $query->select($this->list_fields);

        try {
            $query->allowedFields($this->allowed_fields)
                ->allowedFilters($this->allowed_filters)
                ->allowedSorts($this->allowed_sorts)
                ->allowedIncludes($this->allowed_includes)
                ->allowedAppends($this->allowed_appends);
        } catch (InvalidFieldQuery $exception) {
            return $this->responseGeneralError($exception->getMessage());
        } catch (InvalidFilterQuery $exception) {
            return $this->responseGeneralError($exception->getMessage());
        } catch (InvalidSortQuery $exception) {
            return $this->responseGeneralError($exception->getMessage());
        } catch (InvalidIncludeQuery $exception) {
            return $this->responseGeneralError($exception->getMessage());
        } catch (InvalidAppendQuery $exception){
            return $this->responseGeneralError($exception->getMessage());
        }

        if($this->default_sort != '')
            $query->defaultSort($this->default_sort);

        foreach ($this->constraints as $field => $value) {
            $query->where($field, $value);
        }

        if($this->paginate) {
            $data = $query->paginate();
            return $this->responsePaginated($data);
        }

        $data = $query->get();

        return $this->responseObject($data);
    }

    public function show(Request $request): JsonResponse
    {
        if(!$this->_isAllowed('show')) return $this->responseNotImplemented();

        $model = $this->_getModel($request);
        if(!$model) return $this->responseNotFoundResult();

        return $this->responseObject($model);
    }

    public function store(Request $request): JsonResponse
    {
        if(!$this->_isAllowed('create')) return $this->responseNotImplemented();

        foreach ($this->constraints as $key => $param) {
            $request->request->add([$key => $param]);
        }

        if(!$this->valid($request))
            return $this->responseValidationError();

        $created = ($this->model)::create($request->all());

        return $this->responseCreated($created);
    }

    public function update(Request $request): JsonResponse
    {
        if(!$this->_isAllowed('update')) return $this->responseNotImplemented();

        foreach ($this->constraints as $key => $param) {
            $request->request->add([$key => $param]);
        }

        if(!$this->valid($request, true))
            return $this->responseValidationError();

        $model = $this->_getModel($request);
        if(!$model) return $this->responseNotFoundResult();

        $model->update($request->all());

        return $this->responseUpdated($model);
    }

    public function destroy(Request $request): JsonResponse
    {
        if(!$this->_isAllowed('delete')) return $this->responseNotImplemented();

        $model = $this->_getModel($request);
        if(!$model) return $this->responseNotFoundResult();

        $model->delete();

        return $this->responseDeleted();
    }

    // endregion
    // region order

    public function orderUp(Request $request): JsonResponse
    {
        if(!$this->_isAllowed('up')) return $this->responseNotImplemented();

        $model = $this->_getModel($request);
        if(!$model) return $this->responseNotFoundResult();
        $model->moveOrderUp();

        return $this->responseObject($model->toArray());
    }

    public function orderDown(Request $request): JsonResponse
    {
        if(!$this->_isAllowed('down')) return $this->responseNotImplemented();

        $model = $this->_getModel($request);
        if(!$model) return $this->responseNotFoundResult();
        $model->moveOrderDown();

        return $this->responseObject($model->toArray());
    }

    // endregion
    // region helpers

    /**
     * @param Request $request
     * @return mixed
     */
    protected function _getModel(Request $request)
    {
        $key = array_key_last($request->route()->parameters);
        $id = $request->route()->parameters[$key];

        $query = QueryBuilder::for($this->model);

        if($this->detail_fields != '*')
            $query->select($this->detail_fields);

        try {
            $query->allowedFields($this->allowed_fields)
                ->allowedIncludes($this->allowed_includes)
                ->allowedAppends($this->allowed_appends);
        } catch (InvalidFieldQuery $exception) {
            return $this->responseGeneralError($exception->getMessage());
        } catch (InvalidIncludeQuery $exception) {
            return $this->responseGeneralError($exception->getMessage());
        } catch (InvalidAppendQuery $exception){
            return $this->responseGeneralError($exception->getMessage());
        }

        foreach ($this->constraints as $field => $value) {
            $query->where($field, $value);
        }

        $query->where('id', $id);

        return $query->first();
    }

    protected function _isAllowed(string $operation): bool
    {
        if(count($this->allowed_operations) > 0 && $this->allowed_operations[0] == '*') return true;

        return in_array(
            strtolower($operation),
            array_change_key_case($this->allowed_operations,CASE_LOWER)
        );
    }

    private function _log()
    {
        Log::info(
            'API Request - '.
            $_SERVER['SERVER_ADDR'].' - '.
            request()->method().' - '.
            request()->fullUrl().' - '.
            ((request()->method() == 'PUT' || request()->method() == 'POST') && count(request()->toArray()) > 0 ? ' - '.print_r(request()->toArray(),true) : '')
        );
    }

    // endregion

}
