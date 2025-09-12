// src/app/components/auth/register/register.ts
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar } from '@angular/material/snack-bar';
import { AuthService } from '../../../services/auth';
import { ApiService } from '../../../services/api';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterLink,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule
  ],
  templateUrl: './register.html',
  styleUrls: ['./register.scss']
})
export class RegisterComponent implements OnInit {
  registerForm: FormGroup;
  hidePassword = true;
  hideConfirmPassword = true;
  isLoading = false;
  specialties: any[] = [];

  constructor(
    private fb: FormBuilder,
    private router: Router,
    private authService: AuthService,
    private apiService: ApiService,
    private snackBar: MatSnackBar
  ) {
    this.registerForm = this.fb.group({
      first_name: ['', [Validators.required, Validators.minLength(2)]],
      last_name: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      phone: ['', [Validators.required, Validators.minLength(8)]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      password_confirmation: ['', [Validators.required]],
      user_type: ['patient', Validators.required],
      city: [''],
      specialty_id: ['']
    }, { validators: this.passwordMatchValidator });
  }

  ngOnInit() {
    this.loadSpecialties();
    
    // Écouter les changements du type d'utilisateur
    this.registerForm.get('user_type')?.valueChanges.subscribe(userType => {
      const specialtyControl = this.registerForm.get('specialty_id');
      if (userType === 'doctor') {
        specialtyControl?.setValidators([Validators.required]);
      } else {
        specialtyControl?.clearValidators();
      }
      specialtyControl?.updateValueAndValidity();
    });
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

  passwordMatchValidator(form: FormGroup) {
    const password = form.get('password');
    const confirmPassword = form.get('password_confirmation');
    if (password && confirmPassword && password.value !== confirmPassword.value) {
      confirmPassword.setErrors({ mismatch: true });
      return { mismatch: true };
    }
    return null;
  }

  onSubmit() {
    if (this.registerForm.valid && !this.isLoading) {
      this.isLoading = true;
      
      const userData = this.registerForm.value;
      
      this.authService.register(userData).subscribe({
        next: (response) => {
          this.isLoading = false;
          this.snackBar.open('Inscription réussie ! Connexion en cours...', 'Fermer', {
            duration: 3000
          });
          
          // Redirection selon le type d'utilisateur
          const userType = response.user.user_type || userData.user_type;
          setTimeout(() => {
            this.router.navigate([`/${userType}/dashboard`]);
          }, 1500);
        },
        error: (error) => {
          this.isLoading = false;
          console.error('Erreur lors de l\'inscription:', error);
          
          let errorMessage = 'Erreur lors de l\'inscription';
          if (error.error?.message) {
            errorMessage = error.error.message;
          } else if (error.error?.errors) {
            const errors = Object.values(error.error.errors).flat();
            errorMessage = errors.join(', ');
          }
          
          this.snackBar.open(errorMessage, 'Fermer', {
            duration: 5000
          });
        }
      });
    } else {
      this.snackBar.open('Veuillez remplir tous les champs correctement', 'Fermer', {
        duration: 3000
      });
    }
  }

  togglePasswordVisibility() {
    this.hidePassword = !this.hidePassword;
  }

  toggleConfirmPasswordVisibility() {
    this.hideConfirmPassword = !this.hideConfirmPassword;
  }
}