<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Http\Controllers;

use App\Factory\PaymentFactory;
use App\Filters\PaymentFilters;
use App\Http\Requests\Payment\ActionPaymentRequest;
use App\Http\Requests\Payment\CreatePaymentRequest;
use App\Http\Requests\Payment\DestroyPaymentRequest;
use App\Http\Requests\Payment\EditPaymentRequest;
use App\Http\Requests\Payment\ShowPaymentRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Jobs\Entity\ActionEntity;
use App\Jobs\Invoice\ReverseInvoicePayment;
use App\Models\Payment;
use App\Repositories\BaseRepository;
use App\Repositories\PaymentRepository;
use App\Transformers\PaymentTransformer;
use App\Utils\Traits\MakesHash;
use Illuminate\Http\Request;

/**
 * Class PaymentController
 * @package App\Http\Controllers\PaymentController
 */

class PaymentController extends BaseController
{
    use MakesHash;

    protected $entity_type = Payment::class;

    protected $entity_transformer = PaymentTransformer::class;

    /**
     * @var PaymentRepository
     */
    protected $payment_repo;


    /**
     * PaymentController constructor.
     *
     * @param      \App\Repositories\PaymentRepository  $payment_repo  The invoice repo
     */
    public function __construct(PaymentRepository $payment_repo)
    {
        parent::__construct();

        $this->payment_repo = $payment_repo;
    }

    /**
     * Show the list of Invoices
     *
     * @param      \App\Filters\PaymentFilters  $filters  The filters
     *
     * @return \Illuminate\Http\Response
     *
     *
     *
     * @OA\Get(
     *      path="/api/v1/payments",
     *      operationId="getPayments",
     *      tags={"payments"},
     *      summary="Gets a list of payments",
     *      description="Lists payments, search and filters allow fine grained lists to be generated.

        Query parameters can be added to performed more fine grained filtering of the payments, these are handled by the PaymentFilters class which defines the methods available",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Response(
     *          response=200,
     *          description="A list of payments",
     *          @OA\Header(header="X-API-Version", ref="#/components/headers/X-API-Version"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/Payment"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),

     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     *
     */
    public function index(PaymentFilters $filters)
    {
        $payments = Payment::filter($filters);
      
        return $this->listResponse($payments);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param      \App\Http\Requests\Payment\CreatePaymentRequest  $request  The request
     *
     * @return \Illuminate\Http\Response
     *
     *
     *
     * @OA\Get(
     *      path="/api/v1/payments/create",
     *      operationId="getPaymentsCreate",
     *      tags={"payments"},
     *      summary="Gets a new blank Payment object",
     *      description="Returns a blank object with default values",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Response(
     *          response=200,
     *          description="A blank Payment object",
     *          @OA\Header(header="X-API-Version", ref="#/components/headers/X-API-Version"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/Payment"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     *
     */
    public function create(CreatePaymentRequest $request)
    {
        $payment = PaymentFactory::create(auth()->user()->company()->id, auth()->user()->id);

        return $this->itemResponse($payment);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param      \App\Http\Requests\Payment\StorePaymentRequest  $request  The request
     *
     * @return \Illuminate\Http\Response
     *
     *
     *
     * @OA\Post(
     *      path="/api/v1/payments",
     *      operationId="storePayment",
     *      tags={"payments"},
     *      summary="Adds a Payment",
     *      description="Adds an Payment to the system",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\RequestBody(
     *         description="The payment request",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="amount",
     *                     description="The payment amount",
     *                     type="number",
     *                     format="float",
     *                 ),
     *                 @OA\Property(
     *                     property="date",
     *                     example="2019/12/1",
     *                     description="The payment date",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="transation_reference",
     *                     example="sdfasdfs98776d6kbkfd",
     *                     description="The transaction reference for the payment",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="invoices",
     *                     example="j7s76d,s8765afk,D8fj3Sfdj",
     *                     description="A comma separated list of invoice hashed ids that this payment relates to",
     *                     type="string",
     *                 )
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns the saved Payment object",
     *          @OA\Header(header="X-API-Version", ref="#/components/headers/X-API-Version"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/Payment"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     *
     */
    public function store(StorePaymentRequest $request)
    {
        $payment = $this->payment_repo->save($request, PaymentFactory::create(auth()->user()->company()->id, auth()->user()->id));

        return $this->itemResponse($payment);
    }

    /**
     * Display the specified resource.
     *
     * @param      \App\Http\Requests\Payment\ShowPaymentRequest  $request  The request
     * @param      \App\Models\Invoice                            $payment  The invoice
     *
     * @return \Illuminate\Http\Response
     *
     *
     * @OA\Get(
     *      path="/api/v1/payments/{id}",
     *      operationId="showPayment",
     *      tags={"payments"},
     *      summary="Shows an Payment",
     *      description="Displays an Payment by id",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The Payment Hashed ID",
     *          example="D2J234DFA",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              format="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns the Payment object",
     *          @OA\Header(header="X-API-Version", ref="#/components/headers/X-API-Version"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/Payment"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     *
     */
    public function show(ShowPaymentRequest $request, Payment $payment)
    {
        return $this->itemResponse($payment);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param      \App\Http\Requests\Payment\EditPaymentRequest  $request  The request
     * @param      \App\Models\Invoice                            $payment  The invoice
     *
     * @return \Illuminate\Http\Response
     *
     *
     * @OA\Get(
     *      path="/api/v1/payments/{id}/edit",
     *      operationId="editPayment",
     *      tags={"payments"},
     *      summary="Shows an Payment for editting",
     *      description="Displays an Payment by id",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The Payment Hashed ID",
     *          example="D2J234DFA",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              format="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns the Payment object",
     *          @OA\Header(header="X-API-Version", ref="#/components/headers/X-API-Version"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/Payment"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     *
     */
    public function edit(EditPaymentRequest $request, Payment $payment)
    {
        return $this->itemResponse($payment);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param      \App\Http\Requests\Payment\UpdatePaymentRequest  $request  The request
     * @param      \App\Models\Invoice                              $payment  The invoice
     *
     * @return \Illuminate\Http\Response
     *
     *
     * @OA\Put(
     *      path="/api/v1/payments/{id}",
     *      operationId="updatePayment",
     *      tags={"payments"},
     *      summary="Updates an Payment",
     *      description="Handles the updating of an Payment by id",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The Payment Hashed ID",
     *          example="D2J234DFA",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              format="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns the Payment object",
     *          @OA\Header(header="X-API-Version", ref="#/components/headers/X-API-Version"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/Payment"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     *
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        if($request->entityIsDeleted($payment))
            return $request->disallowUpdate();
        
        $payment = $this->payment_repo->save(request(), $payment);

        return $this->itemResponse($payment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param      \App\Http\Requests\Payment\DestroyPaymentRequest  $request
     * @param      \App\Models\Invoice                               $payment
     *
     * @return     \Illuminate\Http\Response
     *
     *
     * @OA\Delete(
     *      path="/api/v1/payments/{id}",
     *      operationId="deletePayment",
     *      tags={"payments"},
     *      summary="Deletes a Payment",
     *      description="Handles the deletion of an Payment by id",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The Payment Hashed ID",
     *          example="D2J234DFA",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              format="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns a HTTP status",
     *          @OA\Header(header="X-API-Version", ref="#/components/headers/X-API-Version"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     *
     */
    public function destroy(DestroyPaymentRequest $request, Payment $payment)
    {
        ReverseInvoicePayment::dispatchNow($payment, $payment->company);

        $payment->is_deleted = true;
        $payment->save();
        $payment->delete();

        return $this->itemResponse($payment);
    }

    /**
     * Perform bulk actions on the list view
     *
     * @return Collection
     *
     *
     * @OA\Post(
     *      path="/api/v1/payments/bulk",
     *      operationId="bulkPayments",
     *      tags={"payments"},
     *      summary="Performs bulk actions on an array of payments",
     *      description="",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/index"),
     *      @OA\RequestBody(
     *         description="User credentials",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="array",
     *                 @OA\Items(
     *                     type="integer",
     *                     description="Array of hashed IDs to be bulk 'actioned",
     *                     example="[0,1,2,3]",
     *                 ),
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="The Payment response",
     *          @OA\Header(header="X-API-Version", ref="#/components/headers/X-API-Version"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/Payment"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),

     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     *
     */
    public function bulk()
    {
        $action = request()->input('action');
        
        $ids = request()->input('ids');

        $payments = Payment::withTrashed()->find($this->transformKeys($ids));

        $payments->each(function ($payment, $key) use ($action) {
            if (auth()->user()->can('edit', $payment)) {
                $this->payment_repo->{$action}($payment);
            }
        });

        return $this->listResponse(Payment::withTrashed()->whereIn('id', $this->transformKeys($ids)));
    }

    /**
     * Payment Actions
     *
     *
     * @OA\Get(
     *      path="/api/v1/payments/{id}/{action}",
     *      operationId="actionPayment",
     *      tags={"payments"},
     *      summary="Performs a custom action on an Payment",
     *      description="Performs a custom action on an Payment.

        The current range of actions are as follows
        - clone_to_Payment
        - clone_to_quote
        - history
        - delivery_note
        - mark_paid
        - download
        - archive
        - delete
        - email",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="The Payment Hashed ID",
     *          example="D2J234DFA",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              format="string",
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="action",
     *          in="path",
     *          description="The action string to be performed",
     *          example="clone_to_quote",
     *          required=true,
     *          @OA\Schema(
     *              type="string",
     *              format="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns the Payment object",
     *          @OA\Header(header="X-API-Version", ref="#/components/headers/X-API-Version"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *          @OA\JsonContent(ref="#/components/schemas/Payment"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     *
     */
    public function action(ActionPaymentRequest $request, Payment $payment, $action)
    {
        switch ($action) {
            case 'clone_to_invoice':
                //$payment = CloneInvoiceFactory::create($payment, auth()->user()->id);
                //return $this->itemResponse($payment);
                break;
            case 'clone_to_quote':
                //$quote = CloneInvoiceToQuoteFactory::create($payment, auth()->user()->id);
                // todo build the quote transformer and return response here
                break;
            case 'history':
                # code...
                break;
            case 'delivery_note':
                # code...
                break;
            case 'mark_paid':
                # code...
                break;
            case 'archive':
                # code...
                break;
            case 'delete':
                # code...
                break;
            case 'email':
                //dispatch email to queue
                break;

            default:
                # code...
                break;
        }
    }
}
