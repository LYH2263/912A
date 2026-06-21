<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\CustomerRepository;

class CustomerService
{
    public function __construct(
        private CustomerRepository $repository
    ) {
    }

    public function create(array $data): Customer
    {
        return $this->repository->create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        return $this->repository->update($customer, $data);
    }

    public function delete(Customer $customer): bool
    {
        return $this->repository->delete($customer);
    }

    public function findOrCreateByShippingInfo(string $name, string $phone, ?string $address = null): Customer
    {
        $customer = $this->repository->findByPhone($phone);

        if ($customer) {
            $updateData = [];
            if ($customer->name !== $name) {
                $updateData['name'] = $name;
            }
            if ($address && $customer->address !== $address) {
                $updateData['address'] = $address;
            }
            if (!empty($updateData)) {
                $customer = $this->repository->update($customer, $updateData);
            }
            return $customer;
        }

        return $this->repository->create([
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'order_count' => 0,
            'total_spent' => 0,
        ]);
    }

    public function updateStats(Customer $customer): void
    {
        $customer->updateStats();
    }
}
