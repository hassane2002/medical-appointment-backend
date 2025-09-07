// src/app/services/auth.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { tap } from 'rxjs/operators';

export interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  role_id: number;
  phone?: string;
  date_of_birth?: string;
  address?: string;
  city?: string;
  is_active: boolean;
  // Champs calculés pour compatibilité
  name?: string;
  user_type?: 'patient' | 'doctor' | 'admin';
  // Relations
  doctor?: Doctor;
}

export interface Doctor {
  id: number;
  user_id: number;
  specialty_id: number;
  license_number?: string;
  years_of_experience?: number;
  consultation_fee?: number;
  cabinet_address?: string;
  cabinet_phone?: string;
  bio?: string;
  is_verified: boolean;
  specialty?: Specialty;
}

export interface Specialty {
  id: number;
  name: string;
  description?: string;
  consultation_price: number;
}

export interface AuthResponse {
  user: User;
  token: string;
  message: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://localhost:8000/api'; // URL de votre API Laravel
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(private http: HttpClient) {
    // Vérifier si un token existe déjà au démarrage
    this.checkExistingToken();
  }

  // Vérifier si un token existe dans le localStorage
  private checkExistingToken() {
    const token = this.getToken();
    if (token) {
      // TODO: Valider le token avec l'API
      // Pour l'instant, on simule un utilisateur connecté
      const userData = localStorage.getItem('user');
      if (userData) {
        this.currentUserSubject.next(JSON.parse(userData));
      }
    }
  }

  // Connexion
  login(credentials: { email: string; password: string; user_type: string }): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/auth/login`, {
      email: credentials.email,
      password: credentials.password,
      user_type: credentials.user_type
    }).pipe(
      tap(response => {
        // Sauvegarder le token et les données utilisateur
        localStorage.setItem('token', response.token);
        
        // Mapper la réponse pour ajouter les champs compatibles
        const mappedUser = this.mapUserResponse(response.user);
        localStorage.setItem('user', JSON.stringify(mappedUser));
        this.currentUserSubject.next(mappedUser);
      })
    );
  }

  
  // Inscription
register(userData: {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone: string;
  user_type: string;
  city?: string;
  specialty_id?: number;
}): Observable<AuthResponse> {
  // Adapter les données pour votre API Laravel
  const larravelData = {
    first_name: userData.first_name,
    last_name: userData.last_name,
    email: userData.email,
    password: userData.password,
    password_confirmation: userData.password_confirmation,
    phone: userData.phone,
    user_type: userData.user_type,
    city: userData.city || '',
    specialty_id: userData.specialty_id
  };

  console.log('Données envoyées pour inscription:', larravelData);

  return this.http.post<AuthResponse>(`${this.apiUrl}/auth/register`, larravelData).pipe(
    tap(response => {
      if (response.user && response.token) {
        const mappedUser = this.mapUserResponse(response.user);
        localStorage.setItem('token', response.token);
        localStorage.setItem('user', JSON.stringify(mappedUser));
        this.currentUserSubject.next(mappedUser);
      }
    })
  );
}

  // Déconnexion
  logout(): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/logout`, {}).pipe(
      tap(() => {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        this.currentUserSubject.next(null);
      })
    );
  }

  // Obtenir le token
  getToken(): string | null {
    return localStorage.getItem('token');
  }

  // Vérifier si l'utilisateur est connecté
  isLoggedIn(): boolean {
    return !!this.getToken();
  }

  // Obtenir l'utilisateur actuel
  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  // Vérifier le rôle de l'utilisateur
  hasRole(role: 'patient' | 'doctor' | 'admin'): boolean {
    const user = this.getCurrentUser();
    return user ? user.user_type === role : false;
  }

  // Mapper la réponse utilisateur de Laravel vers notre interface
  private mapUserResponse(user: any): User {
    // Mapper role.name vers user_type (si la relation role est incluse)
    let userType: 'patient' | 'doctor' | 'admin' = 'patient';
    
    if (user.role && user.role.name) {
      userType = user.role.name;
    } else {
      // Fallback basé sur role_id si pas de relation
      const roleMap = {
        1: 'patient' as const,
        2: 'doctor' as const,  
        3: 'admin' as const
      };
      userType = roleMap[user.role_id as keyof typeof roleMap] || 'patient';
    }

    return {
      ...user,
      name: `${user.first_name} ${user.last_name}`,
      user_type: userType
    };
  }
}