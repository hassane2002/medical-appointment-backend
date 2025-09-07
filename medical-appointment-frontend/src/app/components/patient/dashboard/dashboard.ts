import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ApiService } from '../../../services/api';
import { AuthService } from '../../../services/auth';

@Component({
  selector: 'app-patient-dashboard',
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
    MatProgressSpinnerModule
  ],
  templateUrl: './dashboard.html',
  styleUrls: ['./dashboard.scss']
})
export class PatientDashboardComponent implements OnInit {
  currentUser: any = null;
  searchForm: FormGroup;
  doctors: any[] = [];
  specialties: any[] = [];
  isLoading = false;

  constructor(
    private fb: FormBuilder,
    private router: Router,
    private apiService: ApiService,
    private authService: AuthService,
    private snackBar: MatSnackBar
  ) {
    this.searchForm = this.fb.group({
      specialty: [''],
      city: [''],
      name: ['']
    });
  }

  ngOnInit() {
    this.currentUser = this.authService.getCurrentUser();
    this.loadSpecialties();
    this.searchDoctors();
  }

  loadSpecialties() {
    this.apiService.getSpecialities().subscribe({
      next: (specialties) => {
        this.specialties = specialties;
      },
      error: (error) => {
        console.error('Erreur lors du chargement des spécialités:', error);
      }
    });
  }

  searchDoctors() {
    this.isLoading = true;
    const searchData = this.searchForm.value;

    this.apiService.searchDoctors(searchData.specialty, searchData.city).subscribe({
      next: (response: any) => {
        console.log('Réponse complète de l\'API:', response);
        
        if (response.success && response.data && response.data.doctors) {
          this.doctors = response.data.doctors;
        } else if (Array.isArray(response)) {
          this.doctors = response;
        } else {
          this.doctors = [];
        }
        
        console.log('Médecins extraits:', this.doctors);
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Erreur lors de la recherche:', error);
        this.snackBar.open('Erreur lors de la recherche de médecins', 'Fermer', {
          duration: 3000
        });
        this.isLoading = false;
      }
    });
  }

  bookAppointment(doctor: any) {
    this.router.navigate(['/patient/book-appointment'], {
      queryParams: { 
        doctor: JSON.stringify(doctor)
      }
    });
  }

  viewDoctorDetails(doctor: any) {
    console.log('Voir les détails du médecin:', doctor);
  }

  resetSearch() {
    this.searchForm.reset();
    this.searchDoctors();
  }

  getDoctorName(doctor: any): string {
    if (doctor.user) {
      return `${doctor.user.first_name} ${doctor.user.last_name}`;
    }
    return doctor.name || 'Nom non disponible';
  }

  getDoctorSpecialty(doctor: any): string {
    if (doctor.specialty) {
      return doctor.specialty.name;
    }
    return 'Spécialité non disponible';
  }

  getDoctorLocation(doctor: any): string {
    if (doctor.cabinet_address) {
      return doctor.cabinet_address;
    }
    if (doctor.user && doctor.user.city) {
      return doctor.user.city;
    }
    return 'Localisation non disponible';
  }

  getDoctorExperience(doctor: any): number | null {
    return doctor.years_of_experience || null;
  }

  getDoctorFee(doctor: any): string {
    const fee = doctor.consultation_fee || doctor.specialty?.consultation_price || 0;
    return parseInt(fee).toLocaleString('fr-FR');
  }

  isDoctorVerified(doctor: any): boolean {
    return doctor.is_verified || false;
  }
}