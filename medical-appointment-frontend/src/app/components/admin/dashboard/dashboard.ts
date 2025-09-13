import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatChipsModule } from '@angular/material/chips';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ApiService } from '../../../services/api';
import { AuthService } from '../../../services/auth';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatTabsModule,
    MatChipsModule,
    MatProgressSpinnerModule
  ],
  templateUrl: './dashboard.html',
  styleUrls: ['./dashboard.scss']
})
export class AdminDashboardComponent implements OnInit {
  currentUser: any = null;
  isLoading = false;
  
  // Données statistiques
  statistics = {
    totalUsers: 0,
    totalPatients: 0,
    totalDoctors: 0,
    totalAppointments: 0,
    totalRevenue: 0,
    paidAppointments: 0,
    pendingPayments: 0,
    generatedReceipts: 0
  };

  users: any[] = [];
  appointments: any[] = [];
  payments: any[] = [];
  specialties: any[] = [];

  // Colonnes des tableaux
  usersColumns = ['name', 'email', 'role', 'status', 'actions'];
  appointmentsColumns = ['patient', 'doctor', 'date', 'status', 'payment'];
  paymentsColumns = ['appointment', 'amount', 'method', 'status', 'date'];

  constructor(
    private apiService: ApiService,
    private authService: AuthService
  ) {}

  ngOnInit() {
    this.currentUser = this.authService.getCurrentUser();
    this.loadAdminData();
  }

  loadAdminData() {
    this.isLoading = true;
    this.loadStatistics();
    this.loadUsers();
    this.loadAppointments();
    this.loadSpecialties();
  }

  loadStatistics() {
    // Simulation des statistiques - à remplacer par vraies API
    this.statistics = {
      totalUsers: 15,
      totalPatients: 12,
      totalDoctors: 32,
      totalAppointments: 13,
      totalRevenue: 875000,
      paidAppointments: 124,
      pendingPayments: 25,
      generatedReceipts: 10
    };
  }

  loadUsers() {
    // Simulation - à remplacer par this.apiService.getAllUsers()
    this.users = [
      { id: 1, name: 'Jean Dupont', email: 'jean@test.com', role: 'patient', is_active: true },
      { id: 2, name: 'Dr. Marie Fall', email: 'marie@test.com', role: 'doctor', is_active: true },
      { id: 3, name: 'Admin User', email: 'admin@test.com', role: 'admin', is_active: true }
    ];
  }

  loadAppointments() {
    // Simulation - à remplacer par API réelle
    this.appointments = [
      { 
        id: 1, 
        patient: 'Jean Dupont', 
        doctor: 'Dr. Fall', 
        date: '2025-09-05', 
        status: 'confirmed',
        payment_status: 'paid',
        amount: 25000
      }
    ];
  }

  loadSpecialties() {
    this.apiService.getSpecialities().subscribe({
      next: (specialties) => {
        this.specialties = specialties;
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Erreur lors du chargement des spécialités:', error);
        this.isLoading = false;
      }
    });
  }

  activateUser(userId: number) {
    console.log('Activer utilisateur:', userId);
  }

  deactivateUser(userId: number) {
    console.log('Désactiver utilisateur:', userId);
  }

  deleteUser(userId: number) {
    if (confirm('Supprimer cet utilisateur ? Cette action est irréversible.')) {
      console.log('Supprimer utilisateur:', userId);
    }
  }



  editSpecialty(specialty: any) {
    const newName = prompt('Nouveau nom:', specialty.name);
    const newPrice = prompt('Nouveau prix (FCFA):', specialty.consultation_price);
    
    if (newName && newPrice) {
      const updatedSpecialty = {
        name: newName,
        description: specialty.description,
        consultation_price: parseFloat(newPrice)
      };
      
      // this.apiService.updateSpecialty(specialty.id, updatedSpecialty).subscribe({...});
      console.log('Modifier spécialité:', specialty.id, updatedSpecialty);
    }
  }
  
  deleteSpecialtyConfirm(specialtyId: number) {
    if (confirm('Supprimer cette spécialité ? Cette action est irréversible.')) {
      // this.apiService.deleteSpecialty(specialtyId).subscribe({...});
      console.log('Supprimer spécialité:', specialtyId);
    }
  }
  
  addSpecialty() {
    const name = prompt('Nom de la spécialité:');
    const price = prompt('Prix de consultation (FCFA):');
    
    if (name && price) {
      const newSpecialty = {
        name: name,
        description: `Consultation de ${name.toLowerCase()}`,
        consultation_price: parseFloat(price)
      };
      
      // this.apiService.createSpecialty(newSpecialty).subscribe({...});
      console.log('Nouvelle spécialité:', newSpecialty);
    }
  }

  getRoleLabel(role: string): string {
    const labels = {
      'patient': 'Patient',
      'doctor': 'Médecin', 
      'admin': 'Administrateur'
    };
    return labels[role as keyof typeof labels] || role;
  }

  getStatusLabel(status: string): string {
    const labels = {
      'pending': 'En attente',
      'confirmed': 'Confirmé',
      'completed': 'Terminé',
      'cancelled': 'Annulé',
      'paid': 'Payé',
     
    };
    return labels[status as keyof typeof labels] || status;
  }

  formatCurrency(amount: number): string {
    return amount.toLocaleString('fr-FR') + ' FCFA';
  }
}



