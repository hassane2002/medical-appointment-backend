// src/app/components/patient/book-appointment/book-appointment.ts
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatRadioModule } from '@angular/material/radio';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ApiService } from '../../../services/api';
import { AuthService } from '../../../services/auth';

@Component({
  selector: 'app-book-appointment',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatButtonModule,
    MatIconModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatRadioModule,
    MatProgressSpinnerModule
  ],
  templateUrl: './book-appointment.html',
  styleUrls: ['./book-appointment.scss']
})
export class BookAppointmentComponent implements OnInit {
  appointmentForm: FormGroup;
  doctor: any = null;
  availableSlots: string[] = [];
  isLoading = false;
  isLoadingSlots = false;
  selectedDate: Date | null = null;
  minDate = new Date();

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private apiService: ApiService,
    private authService: AuthService,
    private snackBar: MatSnackBar
  ) {
    this.appointmentForm = this.fb.group({
      appointment_date: ['', Validators.required],
      appointment_time: ['', Validators.required],
      payment_method: ['cabinet', Validators.required],
      notes: ['']
    });
  }

  ngOnInit() {
    // Récupérer les données du médecin depuis les paramètres de route ou localStorage
    this.route.queryParams.subscribe(params => {
      if (params['doctor']) {
        this.doctor = JSON.parse(params['doctor']);
      } else {
        // Rediriger vers la recherche si pas de médecin sélectionné
        this.router.navigate(['/patient/dashboard']);
      }
    });

    // Écouter les changements de date pour charger les créneaux
    this.appointmentForm.get('appointment_date')?.valueChanges.subscribe(date => {
      if (date) {
        this.selectedDate = date;
        this.loadAvailableSlots();
      }
    });
  }

  loadAvailableSlots() {
    if (!this.doctor || !this.selectedDate) return;
  
    console.log('Chargement des créneaux pour:', this.selectedDate);
    this.isLoadingSlots = true;
    this.availableSlots = [];
    this.appointmentForm.patchValue({ appointment_time: '' });
  
    // SOLUTION TEMPORAIRE : Simuler des créneaux
    setTimeout(() => {
      const dayOfWeek = this.selectedDate!.getDay();
      console.log('Jour de la semaine:', dayOfWeek);
      
      // Générer des créneaux pour tous les jours de la semaine
      this.availableSlots = [
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', 
        '11:00', '11:30', '14:00', '14:30', '15:00', '15:30', 
        '16:00', '16:30', '17:00'
      ];
      
      console.log('Créneaux générés:', this.availableSlots);
      this.isLoadingSlots = false;
    }, 500);
  }

  onSubmit() {
    if (this.appointmentForm.valid && !this.isLoading) {
      this.isLoading = true;

      const appointmentData = {
        doctor_id: this.doctor.id,
        appointment_date: this.formatDate(this.appointmentForm.get('appointment_date')?.value),
        appointment_time: this.appointmentForm.get('appointment_time')?.value,
        payment_method: this.appointmentForm.get('payment_method')?.value,
        notes: this.appointmentForm.get('notes')?.value || ''
      };

      console.log('Données de rendez-vous:', appointmentData);

      this.apiService.createAppointment(appointmentData).subscribe({
        next: (response: any) => {
          this.isLoading = false;
          console.log('Rendez-vous créé:', response);
          
          if (response.success) {
            this.snackBar.open(response.message, 'Fermer', {
              duration: 5000
            });

            // Si paiement en ligne requis
            if (response.data.needs_payment) {
              this.snackBar.open('Redirection vers le paiement...', 'Fermer', {
                duration: 3000
              });
              // TODO: Rediriger vers la page de paiement
            }

            // Rediriger vers le dashboard après 2 secondes
            setTimeout(() => {
              this.router.navigate(['/patient/dashboard']);
            }, 2000);
          }
        },
        error: (error) => {
          this.isLoading = false;
          console.error('Erreur lors de la création du rendez-vous:', error);
          
          let errorMessage = 'Erreur lors de la réservation du rendez-vous';
          if (error.error?.message) {
            errorMessage = error.error.message;
          } else if (error.error?.errors) {
            // Gestion des erreurs de validation Laravel
            const errors = Object.values(error.error.errors).flat();
            errorMessage = errors.join(', ');
          }
          
          this.snackBar.open(errorMessage, 'Fermer', {
            duration: 5000
          });
        }
      });
    }
  }

  private formatDate(date: Date): string {
    return date.toISOString().split('T')[0];
  }

  getDoctorName(): string {
    if (!this.doctor) return '';
    return this.doctor.user ? 
      `${this.doctor.user.first_name} ${this.doctor.user.last_name}` : 
      'Médecin';
  }

  getDoctorSpecialty(): string {
    return this.doctor?.specialty?.name || 'Spécialité non disponible';
  }

  getDoctorFee(): string {
    const fee = this.doctor?.consultation_fee || 0;
    return parseInt(fee).toLocaleString('fr-FR');
  }

  goBack() {
    this.router.navigate(['/patient/dashboard']);
  }
}