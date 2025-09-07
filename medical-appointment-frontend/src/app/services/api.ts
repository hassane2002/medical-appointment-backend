// src/app/services/api.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from './auth';

export interface Doctor {
  id: number;
  name: string;
  email: string;
  phone: string;
  speciality: string;
  available_slots?: TimeSlot[];
}

export interface TimeSlot {
  id: number;
  doctor_id: number;
  date: string;
  start_time: string;
  end_time: string;
  is_available: boolean;
}

export interface Appointment {
  id: number;
  patient_id: number;
  doctor_id: number;
  appointment_date: string;
  appointment_time: string;
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
  payment_status: 'pending' | 'paid' | 'refunded';
  payment_method: 'online' | 'cash';
  amount: number;
  notes?: string;
  patient?: any;
  doctor?: Doctor;
}

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl = 'http://localhost:8000/api';

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) { }

  // Headers avec authentification
  private getHeaders(): HttpHeaders {
    const token = this.authService.getToken();
    return new HttpHeaders({
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : ''
    });
  }

  // ============ MÉDECINS ============
  
  // Obtenir tous les médecins
  getDoctors(): Observable<Doctor[]> {
    return this.http.get<Doctor[]>(`${this.apiUrl}/doctors`, {
      headers: this.getHeaders()
    });
  }

  // Rechercher des médecins par spécialité
  searchDoctors(speciality?: string, location?: string): Observable<Doctor[]> {
    let params = '';
    if (speciality) params += `?speciality=${speciality}`;
    if (location) params += `${params ? '&' : '?'}location=${location}`;
    
    return this.http.get<Doctor[]>(`${this.apiUrl}/doctors/search${params}`, {
      headers: this.getHeaders()
    });
  }

  // Obtenir les créneaux disponibles d'un médecin
  getDoctorSlots(doctorId: number, date?: string): Observable<TimeSlot[]> {
    const params = date ? `?date=${date}` : '';
    return this.http.get<TimeSlot[]>(`${this.apiUrl}/doctors/${doctorId}/slots${params}`, {
      headers: this.getHeaders()
    });
  }

  // ============ RENDEZ-VOUS ============

  // Obtenir les créneaux disponibles d'un médecin
  getDoctorAvailability(doctorId: number, date: string): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/patient/doctors/${doctorId}/availability?date=${date}`, {
      headers: this.getHeaders()
    });
  }

  // Créer un rendez-vous (utilise votre vraie API Laravel)
  createAppointment(appointmentData: {
    doctor_id: number;
    appointment_date: string;
    appointment_time: string;
    payment_method: 'online' | 'cabinet';
    notes?: string;
  }): Observable<any> {
    
    // Adapter les données pour correspondre à votre API Laravel
    const larravelData = {
      doctor_id: appointmentData.doctor_id,
      appointment_date: appointmentData.appointment_date,
      appointment_time: appointmentData.appointment_time,
      payment_method: appointmentData.payment_method,
      reason: appointmentData.notes || '' // Votre API attend 'reason' pas 'notes'
    };

    console.log('Données envoyées à Laravel:', larravelData);

    return this.http.post<any>(`${this.apiUrl}/patient/appointments`, larravelData, {
      headers: this.getHeaders()
    });
  }

  // Annuler un rendez-vous
  cancelAppointment(appointmentId: number): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/patient/appointments/${appointmentId}/cancel`, {}, {
      headers: this.getHeaders()
    });
  }

  

  // Obtenir les rendez-vous du médecin connecté (utilise vos routes existantes)
  getDoctorAppointments(): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/doctor/appointments`, {
      headers: this.getHeaders()
    });
  }

  // Confirmer un rendez-vous (médecin)
  confirmAppointment(appointmentId: number): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/doctor/appointments/${appointmentId}/confirm`, {}, {
      headers: this.getHeaders()
    });
  }

  // Refuser un rendez-vous (médecin)
  rejectAppointment(appointmentId: number): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/doctor/appointments/${appointmentId}/reject`, {}, {
      headers: this.getHeaders()
    });
  }

  // Confirmer/Refuser un rendez-vous (médecin)
  updateAppointmentStatus(appointmentId: number, status: 'confirmed' | 'cancelled'): Observable<Appointment> {
    return this.http.patch<Appointment>(`${this.apiUrl}/appointments/${appointmentId}/status`, 
      { status }, 
      { headers: this.getHeaders() }
    );
  }

  // ============ PAIEMENT ============

  // Traiter un paiement
  processPayment(paymentData: {
    appointment_id: number;
    amount: number;
    payment_method: string;
    stripe_token?: string;
  }): Observable<any> {
    return this.http.post(`${this.apiUrl}/payments/process`, paymentData, {
      headers: this.getHeaders()
    });
  }

  // Ajoutez cette méthode dans votre ApiService, avant la méthode generatePDF
getPatientAppointments(): Observable<any> {
  return this.http.get<any>(`${this.apiUrl}/patient/appointments`, {
    headers: this.getHeaders()
  });
}

  // Générer un justificatif PDF
  generatePDF(appointmentId: number): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/appointments/${appointmentId}/pdf`, {
      headers: this.getHeaders(),
      responseType: 'blob'
    });
  }

// Ajouter ces méthodes dans ApiService
createSpecialty(specialty: {name: string, description: string, consultation_price: number}): Observable<any> {
  return this.http.post<any>(`${this.apiUrl}/admin/specialties`, specialty, {
    headers: this.getHeaders()
  });
}

updateSpecialty(id: number, specialty: any): Observable<any> {
  return this.http.put<any>(`${this.apiUrl}/admin/specialties/${id}`, specialty, {
    headers: this.getHeaders()
  });
}

deleteSpecialty(id: number): Observable<any> {
  return this.http.delete<any>(`${this.apiUrl}/admin/specialties/${id}`, {
    headers: this.getHeaders()
  });
}

// Obtenir les vraies statistiques admin
getAdminStatistics(): Observable<any> {
  return this.http.get<any>(`${this.apiUrl}/admin/statistics/overview`, {
    headers: this.getHeaders()
  });
}



  // ============ SPÉCIALITÉS ============

  // Obtenir toutes les spécialités
  getSpecialities(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/specialties`);
  }
}