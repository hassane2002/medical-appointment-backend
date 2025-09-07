// src/app/components/home/landing/landing.ts
import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { RouterModule } from '@angular/router'; // Ajout de l'import RouterModule
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { AuthService } from '../../../services/auth';

@Component({
  selector: 'app-landing',
  standalone: true,
  imports: [
    CommonModule,
    RouterModule, // Ajout de RouterModule dans les imports
    MatCardModule,
    MatButtonModule,
    MatIconModule
  ],
  templateUrl: './landing.html',
  styleUrls: ['./landing.scss']
})
export class LandingComponent {

  constructor(
    private router: Router,
    private authService: AuthService
  ) {}

  navigateToLogin(userType: string) {
    this.router.navigate(['/auth/login'], { 
      queryParams: { type: userType } 
    });
  }

  navigateToRegister(userType: string) {
    this.router.navigate(['/auth/register'], { 
      queryParams: { type: userType } 
    });
  }

  isLoggedIn(): boolean {
    return this.authService.isLoggedIn();
  }

  getCurrentUserDashboard(): string {
    const user = this.authService.getCurrentUser();
    if (user?.user_type) {
      return `/${user.user_type}/dashboard`;
    }
    return '/auth/login';
  }
}