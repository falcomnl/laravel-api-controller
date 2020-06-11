<?php namespace Falcomnl\LaravelApiController\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller {

    protected $validation_errors;

    // region responses

    /**
     * @param $data
     * @return JsonResponse
     */
    protected function responseObject($data): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => null,
            'code' => Response::HTTP_OK,
            'data' => $this->_getData($data),
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * @param $data
     * @return JsonResponse
     */
    protected function responsePaginated($data, array $appends = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => null,
            'code' => Response::HTTP_OK,
            'data' => $this->_getData($data),
            'pagination' => $this->_getPagination($data),
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * @return JsonResponse
     */
    protected function responseNotFoundResult(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Not found',
            'code' => Response::HTTP_NOT_FOUND,
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * @return JsonResponse
     */
    protected function responseNotImplemented(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Not Implemented',
            'code' => Response::HTTP_NOT_IMPLEMENTED,
        ], Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * @return JsonResponse
     */
    protected function responseUnauthorized(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
            'code' => Response::HTTP_UNAUTHORIZED,
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param $data
     * @return JsonResponse
     */
    protected function responseCreated($data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => null,
            'code' => Response::HTTP_CREATED,
            'data' => $this->_getData($data),
        ], Response::HTTP_CREATED);
    }

    /**
     * @param $data
     * @return JsonResponse
     */
    protected function responseUpdated($data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => null,
            'code' => Response::HTTP_OK,
            'data' => $this->_getData($data),
        ], Response::HTTP_OK);
    }

    /**
     * @return JsonResponse
     */
    protected function responseDeleted(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => null,
            'code' => Response::HTTP_OK,
        ], Response::HTTP_OK);
    }

    /**
     * @return JsonResponse
     */
    protected function responseValidationError(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'code' => Response::HTTP_BAD_REQUEST,
            'errors' => $this->validation_errors,
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @return JsonResponse
     */
    protected function responseGeneralError(string $error): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $error,
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @return JsonResponse
     */
    public static function responseRouteNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Not found',
            'code' => \Illuminate\Http\Response::HTTP_NOT_FOUND,
        ], \Illuminate\Http\Response::HTTP_NOT_FOUND);
    }

    // endregion
    // region validation

    /**
     * @param bool $update
     * @return array
     */
    protected function rules(bool $is_update = false): array
    {
        return [];
    }

    /**
     * @param bool $update
     * @return array
     */
    protected function messages(bool $is_update = false): array
    {
        return [];
    }

    /**
     * @param Request $request
     * @param bool $is_update
     * @return bool
     */
    protected function valid(Request $request, bool $is_update = false, $rules = [], $messages = []): bool
    {
        if(empty($rules)) $rules = $this->rules($is_update);
        if(empty($messages)) $messages = $this->messages();

        $validator = Validator::make($request->json()->all(), $rules, $messages);
        if(count($validator->errors()) > 0) {
            $this->validation_errors = $validator->errors();
            return false;
        }

        return true;
    }

    // endregion
    // region helpers

    private function _getData($data): ?array
    {
        if($data instanceof Model || $data instanceof LengthAwarePaginator || $data instanceof Collection)
            $data = $data->toArray();

        if(is_array($data) && isset($data['data']))
            return $data['data'];

        return json_decode(json_encode($data), true);
    }

    private function _getPagination($paginated_data): ?array
    {
        $pagination = $paginated_data->toArray();

        if(isset($pagination['data']) && isset($pagination['current_page'])) {
            unset($pagination['data']);
            return $pagination;
        }

        return null;
    }

    // endregion

}
