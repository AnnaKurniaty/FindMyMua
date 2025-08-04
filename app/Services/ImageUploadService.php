<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    protected $disk = 's3';
    
    public function uploadProfilePhoto(UploadedFile $file, $oldPhoto = null)
    {
        return $this->uploadImage($file, 'images/profile_photos', $oldPhoto);
    }
    
    public function uploadServicePhoto(UploadedFile $file, $oldPhoto = null)
    {
        return $this->uploadImage($file, 'images/service_photos', $oldPhoto);
    }
    
    public function uploadPortfolioImage(UploadedFile $file, $oldPhoto = null)
    {
        return $this->uploadImage($file, 'images/portfolio', $oldPhoto);
    }
    
    public function uploadPaymentProof(UploadedFile $file, $oldPhoto = null)
    {
        return $this->uploadImage($file, 'images/payment_proofs', $oldPhoto);
    }
    
    private function uploadImage(UploadedFile $file, $folder, $oldPhoto = null)
    {
        // Delete old photo if exists
        if ($oldPhoto) {
            $this->deleteImage($oldPhoto);
        }
        
        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $path = $file->storeAs($folder, $filename, $this->disk);
        
        return basename($path);
    }
    
    public function deleteImage($filename, $folder = null)
    {
        if (!$filename) return;
        
        if ($folder) {
            $path = $folder . '/' . $filename;
        } else {
            // Try to determine folder from filename
            $path = $this->determinePath($filename);
        }
        
        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }
    
    public function getImageUrl($filename, $folder = null)
    {
        if (!$filename) return null;
        
        if ($folder) {
            $path = $folder . '/' . $filename;
        } else {
            $path = $this->determinePath($filename);
        }
        
        // Get the full URL from S3/Supabase
        $url = Storage::disk($this->disk)->url($path);
        
        // Ensure the URL is absolute and properly formatted for Supabase
        if (strpos($url, 'http') !== 0) {
            $url = rtrim(env('AWS_URL', 'https://your-supabase-url.supabase.co'), '/') . '/' . ltrim($path, '/');
        }
        
        return $url;
    }
    
    private function determinePath($filename)
    {
        // Determine folder based on filename patterns
        if (strpos($filename, 'profile_') === 0) {
            return 'images/profile_photos/' . $filename;
        }
        
        if (strpos($filename, 'service_') === 0) {
            return 'images/service_photos/' . $filename;
        }
        
        if (strpos($filename, 'portfolio_') === 0) {
            return 'images/portfolio/' . $filename;
        }
        
        if (strpos($filename, 'payment_') === 0) {
            return 'images/payment_proofs/' . $filename;
        }
        
        return 'images/' . $filename;
    }
}
