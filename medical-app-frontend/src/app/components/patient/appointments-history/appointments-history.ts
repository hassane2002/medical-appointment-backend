import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTabsModule } from '@angular/material/tabs';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ApiService } from '../../../services/api';
import { AuthService } from '../../../services/auth';

@Component({
  selector: 'app-appointments-history',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatChipsModule,
    MatProgressSpinnerModule,
    MatTabsModule
  ],
  templateUrl: './appointments-history.html',
  styleUrls: ['./appointments-history.scss']
})
export class AppointmentsHistoryComponent implements OnInit {
  appointments: any[] = [];
  isLoading = false;
  currentUser: any = null;

  constructor(
    private apiService: ApiService,
    private authService: AuthService,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit() {
    this.currentUser = this.authService.getCurrentUser();
    this.loadAppointments();
  }

  loadAppointments() {
    this.isLoading = true;
    
    this.apiService.getPatientAppointments().subscribe({
      next: (response: any) => {
        console.log('Rendez-vous du patient:', response);
        
        if (response.success && response.data) {
          this.appointments = response.data.appointments || [];
        } else if (Array.isArray(response)) {
          this.appointments = response;
        } else {
          this.appointments = [];
        }
        
        this.isLoading = false;
      },
      error: (error: any) => {
        console.error('Erreur lors du chargement des rendez-vous:', error);
        this.isLoading = false;
      }
    });
  }

  getAppointmentsByStatus(status: string) {
    return this.appointments.filter(appointment => appointment.status === status);
  }

  getStatusLabel(status: string): string {
    const statusLabels = {
      'pending': 'En attente',
      'confirmed': 'Confirmé',
      'completed': 'Terminé',
      'cancelled': 'Annulé',
      'no_show': 'Absent'
    };
    return statusLabels[status as keyof typeof statusLabels] || status;
  }

  getStatusColor(status: string): string {
    const statusColors = {
      'pending': 'orange',
      'confirmed': 'primary',
      'completed': 'accent',
      'cancelled': 'warn',
      'no_show': 'warn'
    };
    return statusColors[status as keyof typeof statusColors] || '';
  }

  getPaymentStatusLabel(paymentStatus: string): string {
    const paymentLabels = {
      'pending': 'En attente',
      'paid': 'Payé',
      'refunded': 'Remboursé',
      'failed': 'Échec'
    };
    return paymentLabels[paymentStatus as keyof typeof paymentLabels] || paymentStatus;
  }

  downloadReceipt(appointmentId: number) {
    console.log('Télécharger le justificatif pour:', appointmentId);
    
    this.apiService.generatePDF(appointmentId).subscribe({
      next: (blob: Blob) => {
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `justificatif-rdv-${appointmentId}.pdf`;
        link.click();
        window.URL.revokeObjectURL(url);
        
        this.snackBar.open('Justificatif téléchargé avec succès', 'Fermer', {
          duration: 3000
        });
      },
      error: (error: any) => {
        console.error('Erreur lors du téléchargement:', error);
        this.snackBar.open('Erreur lors du téléchargement du justificatif', 'Fermer', {
          duration: 3000
        });
      }
    });
  }

  cancelAppointment(appointmentId: number) {
    if (confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')) {
      this.apiService.cancelAppointment(appointmentId).subscribe({
        next: (response: any) => {
          if (response.success) {
            this.snackBar.open('Rendez-vous annulé avec succès', 'Fermer', {
              duration: 3000
            });
            this.loadAppointments();
          }
        },
        error: (error: any) => {
          console.error('Erreur lors de l\'annulation:', error);
          let errorMessage = 'Erreur lors de l\'annulation du rendez-vous';
          if (error.error?.message) {
            errorMessage = error.error.message;
          }
          this.snackBar.open(errorMessage, 'Fermer', {
            duration: 5000
          });
        }
      });
    }
  }

  formatDate(date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }

  formatTime(time: string): string {
    return time.substring(0, 5);
  }
}