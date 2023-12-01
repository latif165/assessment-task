<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {
        $this->apiService = $apiService;
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        try {
            $discountCode = $this->apiService->createDiscountCode($merchant);
            $user = User::create(["email" => $email, "name" => $name, "type" => User::TYPE_AFFILIATE]);
            $affiliateData = [
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
                'commission_rate' => $commissionRate,
                'discount_code' => $discountCode['code']
            ];
            $affiliate = Affiliate::create($affiliateData);

            Mail::to($email)->send(new AffiliateCreated($affiliate));

            return $affiliate;
        } catch(QueryException $query) {
            throw new AffiliateCreateException($query);
        }
    }
}
