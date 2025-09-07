<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'message',
        'response',
        'message_type',
    ];

    // Constantes pour les types de messages
    const TYPE_USER = 'user';
    const TYPE_AI = 'ai';

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeUserMessages($query)
    {
        return $query->where('message_type', self::TYPE_USER);
    }

    public function scopeAiMessages($query)
    {
        return $query->where('message_type', self::TYPE_AI);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors
    public function getTypeLabelAttribute()
    {
        return $this->message_type === self::TYPE_USER ? 'Utilisateur' : 'Assistant IA';
    }

    // Méthodes statiques pour créer des conversations
    public static function createConversation($userId, $sessionId, $userMessage, $aiResponse)
    {
        // Sauvegarder le message utilisateur
        self::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'message' => $userMessage,
            'response' => '',
            'message_type' => self::TYPE_USER,
        ]);

        // Sauvegarder la réponse IA
        return self::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'message' => '',
            'response' => $aiResponse,
            'message_type' => self::TYPE_AI,
        ]);
    }
}