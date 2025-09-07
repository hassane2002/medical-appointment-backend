// src/app/components/shared/ai-chat/ai-chat.ts
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ApiService } from '../../../services/api';

@Component({
  selector: 'app-ai-chat',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule
  ],
  templateUrl: './ai-chat.html',
  styleUrls: ['./ai-chat.scss']
})
export class AiChatComponent implements OnInit {
  chatForm: FormGroup;
  messages: any[] = [];
  isLoading = false;

  // Réponses pré-programmées pour simulation
  private responses = {
    'bonjour': 'Bonjour ! Je suis votre assistant médical virtuel. Comment puis-je vous aider aujourd\'hui ?',
    'douleur': 'Pour les douleurs, il est important de consulter un médecin. Puis-je vous aider à trouver un spécialiste ?',
    'fièvre': 'En cas de fièvre persistante, consultez rapidement un médecin. Souhaitez-vous réserver un rendez-vous ?',
    'rendez-vous': 'Je peux vous aider à trouver et réserver un rendez-vous. Allez dans la section "Recherche" pour voir les médecins disponibles.',
    'paiement': 'Vous pouvez payer votre consultation soit en ligne lors de la réservation, soit directement au cabinet médical.',
    'annulation': 'Pour annuler un rendez-vous, allez dans "Mes RDV" et cliquez sur "Annuler" à côté du rendez-vous concerné.',
    'default': 'Je comprends votre question. Pour des conseils médicaux précis, je vous recommande de consulter un professionnel de santé. Puis-je vous aider à trouver un médecin ?'
  };

  constructor(
    private fb: FormBuilder,
    private apiService: ApiService
  ) {
    this.chatForm = this.fb.group({
      message: ['', Validators.required]
    });
  }

  ngOnInit() {
    // Message de bienvenue
    this.messages = [
      {
        type: 'ai',
        content: 'Bonjour ! Je suis votre assistant médical virtuel. Je peux vous aider avec des questions générales sur la santé et vous guider dans l\'utilisation de la plateforme. Comment puis-je vous aider ?',
        timestamp: new Date()
      }
    ];
  }

  sendMessage() {
    if (this.chatForm.valid && !this.isLoading) {
      const userMessage = this.chatForm.get('message')?.value;
      
      // Ajouter le message utilisateur
      this.messages.push({
        type: 'user',
        content: userMessage,
        timestamp: new Date()
      });

      this.chatForm.reset();
      this.isLoading = true;

      // Simuler une réponse IA après 1 seconde
      setTimeout(() => {
        const aiResponse = this.generateAIResponse(userMessage);
        
        this.messages.push({
          type: 'ai',
          content: aiResponse,
          timestamp: new Date()
        });
        
        this.isLoading = false;
        this.scrollToBottom();
      }, 1000);

      this.scrollToBottom();
    }
  }

  private generateAIResponse(message: string): string {
    const lowerMessage = message.toLowerCase();
    
    // Rechercher des mots-clés dans le message
    for (const [keyword, response] of Object.entries(this.responses)) {
      if (keyword !== 'default' && lowerMessage.includes(keyword)) {
        return response;
      }
    }
    
    // Réponse par défaut
    return this.responses.default;
  }

  private scrollToBottom() {
    setTimeout(() => {
      const chatContainer = document.querySelector('.messages-container');
      if (chatContainer) {
        chatContainer.scrollTop = chatContainer.scrollHeight;
      }
    }, 100);
  }

  clearChat() {
    this.messages = [
      {
        type: 'ai',
        content: 'Conversation réinitialisée. Comment puis-je vous aider ?',
        timestamp: new Date()
      }
    ];
  }
}