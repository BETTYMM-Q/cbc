public function getMediaUrlAttribute(): string
{
    if (!$this->media_path) {
        return asset('images/default-ad-placeholder.jpg');
    }

    return \Storage::disk('public')->url($this->media_path);
}

public function isVideo(): bool
{
    $extension = pathinfo($this->media_path, PATHINFO_EXTENSION);
    return in_array(strtolower($extension), ['mp4', 'webm', 'ogg']);
}
