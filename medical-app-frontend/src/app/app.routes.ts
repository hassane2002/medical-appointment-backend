// src/app/app.routes.ts
import { Routes } from '@angular/router';

export const routes: Routes = [
  // Page d'accueil
  {
    path: '',
    loadComponent: () => import('./components/home/landing/landing').then(m => m.LandingComponent)
  }, 
  
  // Routes d'authentification
  { 
    path: 'auth/login', 
    loadComponent: () => import('./components/auth/login/login').then(m => m.LoginComponent)
  },
  { 
    path: 'auth/register', 
    loadComponent: () => import('./components/auth/register/register').then(m => m.RegisterComponent)
  },
  { 
    path: 'auth/profile', 
    loadComponent: () => import('./components/auth/profile/profile').then(m => m.ProfileComponent)
  },
  
  // Routes Patient
  { 
    path: 'patient/dashboard', 
    loadComponent: () => import('./components/patient/dashboard/dashboard').then(m => m.PatientDashboardComponent)
  },
  {
    path: 'patient/book-appointment',
    loadComponent: () => import('./components/patient/book-appointment/book-appointment').then(m => m.BookAppointmentComponent)
  },
  {
    path: 'patient/appointments',
    loadComponent: () => import('./components/patient/appointments-history/appointments-history').then(m => m.AppointmentsHistoryComponent)
  },
  
  // Routes Médecin  
  { 
    path: 'doctor/dashboard', 
    loadComponent: () => import('./components/doctor/dashboard/dashboard').then(m => m.DoctorDashboardComponent)
  },
  
  // Routes Admin
  { 
    path: 'admin/dashboard', 
    loadComponent: () => import('./components/admin/dashboard/dashboard').then(m => m.AdminDashboardComponent)
  },
  
  {
    path: 'patient/ai-chat',
    loadComponent: () => import('./components/shared/ai-chat/ai-chat').then(m => m.AiChatComponent)
  },

  // Route wildcard - doit être en dernier
  { 
    path: '**', 
    redirectTo: '/patient/dashboard' 
  }
];