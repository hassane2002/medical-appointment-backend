// src/app/components/auth/login/login.ts
import { Component } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';

import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatSelectModule } from '@angular/material/select';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBar, MatSnackBarModule } from '@angular/material/snack-bar';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../services/auth';
import { HttpClient } from '@angular/common/http';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterLink,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatSelectModule,
    MatProgressSpinnerModule,
    MatSnackBarModule
  ],
  templateUrl: './login.html',
  styleUrls: ['./login.scss']
})
export class LoginComponent {
  loginForm: FormGroup;
  hidePassword = true;
  isLoading = false;

  constructor(
    private fb: FormBuilder,
    private router: Router,
    private authService: AuthService,
    private snackBar: MatSnackBar,
    private http: HttpClient,
    private route: ActivatedRoute
  ) {
  
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      userType: ['patient', Validators.required]
    });

    this.route.queryParams.subscribe(params => {
      if (params['type']) {
        this.loginForm.patchValue({ userType: params['type'] });
      }
    });
  

    // Rediriger si déjà connecté
    if (this.authService.isLoggedIn()) {
      this.redirectToDashboard();
    }

    // Test CORS au chargement
    this.testCors();
  }

  // Test de connexion CORS avec Laravel
  testCors() {
    this.http.get('http://localhost:8000/api/test-cors').subscribe({
      next: (response) => {
        console.log('✅ CORS fonctionne:', response);
      },
      error: (error) => {
        console.error('❌ Erreur CORS:', error);
      }
    });
  }

  onSubmit() {
    if (this.loginForm.valid && !this.isLoading) {
      this.isLoading = true;
      
      const credentials = {
        email: this.loginForm.get('email')?.value,
        password: this.loginForm.get('password')?.value,
        user_type: this.loginForm.get('userType')?.value // Important: user_type
      };

      console.log('Envoi des credentials:', credentials);

      this.authService.login(credentials).subscribe({
        next: (response) => {
          console.log('Réponse de connexion:', response);
          this.isLoading = false;
          this.snackBar.open('Connexion réussie !', 'Fermer', {
            duration: 3000,
            panelClass: ['success-snackbar']
          });
          
          this.redirectToDashboard();
        },
        error: (error) => {
          this.isLoading = false;
          console.error('Erreur complète:', error);
          console.error('Status:', error.status);
          console.error('Message:', error.error);
          
          let errorMessage = 'Erreur de connexion. Vérifiez vos identifiants.';
          if (error.error?.message) {
            errorMessage = error.error.message;
          }
          
          this.snackBar.open(errorMessage, 'Fermer', {
            duration: 5000,
            panelClass: ['error-snackbar']
          });
        }
      });
    } else {
      this.snackBar.open('Veuillez remplir tous les champs correctement.', 'Fermer', {
        duration: 3000,
        panelClass: ['warning-snackbar']
      });
    }
  }


  
  private redirectToDashboard() {
    const user = this.authService.getCurrentUser();
    if (user) {
      this.router.navigate([`/${user.user_type}/dashboard`]);
    } else {
      // Fallback si pas d'utilisateur
      const userType = this.loginForm.get('userType')?.value || 'patient';
      this.router.navigate([`/${userType}/dashboard`]);
    }
  }

  togglePasswordVisibility() {
    this.hidePassword = !this.hidePassword;
  }
}