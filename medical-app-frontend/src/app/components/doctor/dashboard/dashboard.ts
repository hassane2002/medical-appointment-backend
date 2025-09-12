// Remplacez complètement src/app/components/doctor/dashboard/dashboard.ts
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTabsModule } from '@angular/material/tabs';
import { MatChipsModule } from '@angular/material/chips';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ApiService } from '../../../services/api';
import { AuthService } from '../../../services/auth';

@Component({
  selector: 'app-doctor-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatTabsModule,
    MatChipsModule,
    MatProgressSpinnerModule
  ],
  templateUrl: './dashboard.html',
  styleUrls: ['./dashboard.scss']
})
export class DoctorDashboardComponent implements OnInit {
  currentUser: any = null;
  appointments: any[] = [];
  isLoading = false;
  statistics = {
    pending: 0,
    confirmed: 0,
    completed: 0,
    todayAppointments: 0
  };

  constructor(
    private apiService: ApiService,
    private authService: AuthService
  ) {}

  ngOnInit() {
    this.currentUser = this.authService.getCurrentUser();
    this.loadDoctorAppointments();
  }

  loadDoctorAppointments() {
    this.isLoading = true;
    
    this.apiService.getDoctorAppointments().subscribe({
      next: (response: any) => {
        if (response.success && response.data) {
          this.appointments = response.data.appointments || [];
        } else if (Array.isArray(response)) {
          this.appointments = response;
        } else {
          this.appointments = [];
        }
        
        this.calculateStatistics();
        this.isLoading = false;
      },
      error: (error: any) => {
        console.error('Erreur lors du chargement des rendez-vous:', error);
        this.isLoading = false;
      }
    });
  }

  



  calculateStatistics() {
    const today = new Date().toISOString().split('T')[0];
    
    this.statistics = {
      pending: this.appointments.filter(a => a.status === 'pending').length,
      confirmed: this.appointments.filter(a => a.status === 'confirmed').length,
      completed: this.appointments.filter(a => a.status === 'completed').length,
      todayAppointments: this.appointments.filter(a => a.appointment_date === today).length
    };
  }

  getAppointmentsByStatus(status: string) {
    return this.appointments.filter(appointment => appointment.status === status);
  }

  confirmAppointment(appointmentId: number) {
    if (confirm('Confirmer ce rendez-vous ?')) {
      this.apiService.confirmAppointment(appointmentId).subscribe({
        next: (response: any) => {
          if (response.success) {
            console.log('Rendez-vous confirmé');
            // Recharger la liste
            this.loadDoctorAppointments();
          }
        },
        error: (error: any) => {
          console.error('Erreur lors de la confirmation:', error);
        }
      });
    }
  }

  rejectAppointment(appointmentId: number) {
    if (confirm('Refuser ce rendez-vous ? Cette action est irréversible.')) {
      this.apiService.rejectAppointment(appointmentId).subscribe({
        next: (response: any) => {
          if (response.success) {
            console.log('Rendez-vous refusé');
            // Recharger la liste
            this.loadDoctorAppointments();
          }
        },
        error: (error: any) => {
          console.error('Erreur lors du refus:', error);
        }
      });
    }
  }
  getStatusLabel(status: string): string {
    const statusLabels = {
      'pending': 'En attente',
      'confirmed': 'Confirmé',
      'completed': 'Terminé',
      'cancelled': 'Annulé'
    };
    return statusLabels[status as keyof typeof statusLabels] || status;
  }

  formatDate(date: string): string {
    return new Date(date).toLocaleDateString('fr-FR');
  }

  formatTime(time: string): string {
    return time.substring(0, 5);
  }
}

