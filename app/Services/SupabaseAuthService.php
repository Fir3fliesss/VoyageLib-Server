<?php

namespace App\Services;

use Config;
use PHPSupabase\Service;
use Exception;

class SupabaseAuthService{
    protected $auth;
    
    public function __construct()
    {
        $service = new Service(
        config('services.supabase.anon_key'),
        config('services.supabase.url'),
        );
        $this->auth = $service->createAuth();
    }
    
    public function register(string $email, string $password, array $metadata = [])
    {
        $this->auth->createUserWithEmailAndPassword($email, $password, $metadata);
        return $this->auth->data();
    }
    
    public function login(string $email, string $password)
    {
        $this->auth->signInWithEmailAndPassword($email, $password);
        $data = $this->auth->data();

        return (object) [
            'access_token' => $data->access_token,
            'refresh_token' => $data->refresh_token,
            'user' => $data->user
        ];
    }
    
    public function signOut(string $access_token)
    {
        $this->auth->signOut($access_token);
        return true;
    }
    
    public function getUser(string $access_token)
    {
        return $this->auth->getUser($access_token);
    }
}