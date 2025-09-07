<?php
// app/Services/AiChatService.php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\User;
use OpenAI\Laravel\Facades\OpenAI;

class AiChatService
{
    protected $maxTokens;
    protected $temperature;
    protected $model;

    public function __construct()
    {
        $this->maxTokens = config('medical.ai.openai.max_tokens', 150);
        $this->temperature = config('medical.ai.openai.temperature', 0.7);
        $this->model = config('medical.ai.openai.model', 'gpt-3.5-turbo');
    }

    /**
     * Traiter une conversation IA
     */
    public function processChat(User $user, string $message, string $sessionId = null)
    {
        if (!config('medical.ai.enabled', true)) {
            return [
                'success' => false,
                'message' => 'Le chat IA est actuellement désactivé.'
            ];
        }

        // Vérifier les limites
        if (!$this->checkRateLimit($user)) {
            return [
                'success' => false,
                'message' => 'Limite de conversations atteinte. Réessayez plus tard.'
            ];
        }

        $sessionId = $sessionId ?? $this->generateSessionId($user->id);

        try {
            // Vérifier le contenu du message
            if ($this->containsRestrictedContent($message)) {
                return [
                    'success' => false,
                    'message' => $this->getRestrictedContentResponse($message)
                ];
            }

            // Construire le contexte de la conversation
            $context = $this->buildConversationContext($user, $sessionId);
            
            // Appel à OpenAI
            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => array_merge($context, [
                    ['role' => 'user', 'content' => $message]
                ]),
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ]);

            $aiResponse = $response->choices[0]->message->content;

            // Sauvegarder la conversation
            AiConversation::createConversation(
                $user->id,
                $sessionId,
                $message,
                $aiResponse
            );

            return [
                'success' => true,
                'response' => $aiResponse,
                'session_id' => $sessionId,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors du traitement de votre demande. Réessayez plus tard.'
            ];
        }
    }

    /**
     * Construire le contexte de conversation
     */
    protected function buildConversationContext(User $user, string $sessionId)
    {
        $systemMessage = [
            'role' => 'system',
            'content' => $this->getSystemPrompt($user)
        ];

        $recentConversations = AiConversation::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit(config('medical.ai.conversation.max_length', 10))
            ->get()
            ->reverse();

        $context = [$systemMessage];

        foreach ($recentConversations as $conv) {
            if ($conv->message_type === 'user') {
                $context[] = ['role' => 'user', 'content' => $conv->message];
            } else {
                $context[] = ['role' => 'assistant', 'content' => $conv->response];
            }
        }

        return $context;
    }

    /**
     * Prompt système pour l'IA
     */
    protected function getSystemPrompt(User $user)
    {
        return "Tu es un assistant virtuel pour une plateforme de prise de rendez-vous médicaux. 
                Ton rôle est d'aider les utilisateurs avec des questions générales sur la santé, 
                la navigation sur la plateforme, et la prise de rendez-vous.
                
                IMPORTANT:
                - Tu ne peux PAS faire de diagnostic médical
                - Tu ne peux PAS donner de conseils sur les médicaments
                - Pour les urgences médicales, dirige vers le 15
                - Reste bienveillant et professionnel
                - Réponds en français
                
                L'utilisateur est un {$user->role->name} nommé {$user->first_name}.";
    }

    /**
     * Vérifier les limites de taux
     */
    protected function checkRateLimit(User $user)
    {
        $hourlyLimit = config('medical.ai.conversation.rate_limit', 50);
        $recentChats = AiConversation::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $recentChats < $hourlyLimit;
    }

    /**
     * Vérifier le contenu restreint
     */
    protected function containsRestrictedContent(string $message)
    {
        $restrictedKeywords = [
            'diagnostic', 'médicament', 'posologie', 'urgence grave',
            'douleur intense', 'malaise', 'évanouissement'
        ];

        foreach ($restrictedKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Réponse pour contenu restreint
     */
    protected function getRestrictedContentResponse(string $message)
    {
        if (stripos($message, 'urgence') !== false || stripos($message, 'grave') !== false) {
            return "Pour une urgence médicale, contactez immédiatement le 15 (SAMU) ou rendez-vous aux urgences les plus proches.";
        }

        if (stripos($message, 'diagnostic') !== false) {
            return "Je ne peux pas établir de diagnostic médical. Consultez un médecin sur notre plateforme pour un avis professionnel.";
        }

        if (stripos($message, 'médicament') !== false) {
            return "Je ne peux pas donner de conseils sur les médicaments. Consultez votre médecin ou pharmacien.";
        }

        return "Pour cette question de nature médicale, je vous recommande de consulter un professionnel de santé sur notre plateforme.";
    }

    /**
     * Générer un ID de session
     */
    protected function generateSessionId(int $userId)
    {
        return 'chat_' . $userId . '_' . time();
    }
}
