// src/app/services/pdf-generator.ts
import { Injectable } from '@angular/core';
import jsPDF from 'jspdf';

@Injectable({
  providedIn: 'root'
})
export class PdfGeneratorService {

  generateAppointmentReceipt(appointmentData: any): void {
    const pdf = new jsPDF();
    
    // En-tête médical
    pdf.setFillColor(63, 81, 181);
    pdf.rect(0, 0, 210, 40, 'F');
    
    // Logo et titre
    pdf.setTextColor(255, 255, 255);
    pdf.setFontSize(24);
    pdf.text('🏥 MEDICAL APPOINTMENT', 20, 25);
    pdf.setFontSize(12);
    pdf.text('Justificatif de rendez-vous médical', 20, 35);
    
    // Numéro de référence
    pdf.setTextColor(0, 0, 0);
    pdf.setFontSize(10);
    const referenceNumber = appointmentData.reference_number || `RDV-${Date.now()}`;
    pdf.text(`Référence: ${referenceNumber}`, 140, 50);
    pdf.text(`Date d'émission: ${new Date().toLocaleDateString('fr-FR')}`, 140, 57);
    
    // Informations patient
    pdf.setFontSize(14);
    pdf.setFont('helvetica', 'bold');
    pdf.text('INFORMATIONS PATIENT', 20, 70);
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(11);
    pdf.text(`Nom: ${appointmentData.patient_name}`, 20, 82);
    pdf.text(`Email: ${appointmentData.patient_email || 'N/A'}`, 20, 90);
    pdf.text(`Téléphone: ${appointmentData.patient_phone || 'N/A'}`, 20, 98);
    
    // Informations médecin
    pdf.setFontSize(14);
    pdf.setFont('helvetica', 'bold');
    pdf.text('INFORMATIONS MÉDECIN', 20, 120);
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(11);
    pdf.text(`Médecin: ${appointmentData.doctor_name}`, 20, 132);
    pdf.text(`Spécialité: ${appointmentData.specialty}`, 20, 140);
    pdf.text(`Cabinet: ${appointmentData.cabinet_address || 'N/A'}`, 20, 148);
    pdf.text(`Téléphone: ${appointmentData.cabinet_phone || 'N/A'}`, 20, 156);
    
    // Détails du rendez-vous
    pdf.setFontSize(14);
    pdf.setFont('helvetica', 'bold');
    pdf.text('DÉTAILS DU RENDEZ-VOUS', 20, 180);
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(11);
    pdf.text(`Date: ${appointmentData.appointment_date}`, 20, 192);
    pdf.text(`Heure: ${appointmentData.appointment_time}`, 20, 200);
    pdf.text(`Durée estimée: 30 minutes`, 20, 208);
    
    // Informations de paiement
    pdf.setFontSize(14);
    pdf.setFont('helvetica', 'bold');
    pdf.text('INFORMATIONS PAIEMENT', 20, 230);
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(11);
    pdf.text(`Montant: ${appointmentData.amount || 0} FCFA`, 20, 242);
    pdf.text(`Mode de paiement: ${appointmentData.payment_method === 'online' ? 'En ligne' : 'Au cabinet'}`, 20, 250);
    pdf.text(`Statut: ${appointmentData.payment_status === 'paid' ? 'Payé' : 'En attente'}`, 20, 258);
    
    // QR Code (simple simulation avec texte)
    pdf.setFillColor(240, 240, 240);
    pdf.rect(140, 180, 50, 50, 'F');
    pdf.setFontSize(8);
    pdf.text('QR CODE', 155, 200);
    pdf.text(referenceNumber, 145, 210);
    pdf.text('Scannez pour', 150, 218);
    pdf.text('vérifier', 155, 225);
    
    // Instructions importantes
    pdf.setFontSize(10);
    pdf.setFont('helvetica', 'bold');
    pdf.text('INSTRUCTIONS IMPORTANTES:', 20, 280);
    pdf.setFont('helvetica', 'normal');
    pdf.setFontSize(9);
    pdf.text('• Présentez-vous 15 minutes avant votre rendez-vous', 20, 290);
    pdf.text('• Munissez-vous de votre carte d\'identité et de ce justificatif', 20, 298);
    pdf.text('• En cas d\'empêchement, prévenez au moins 24h à l\'avance', 20, 306);
    
    // Footer
    pdf.setFillColor(245, 245, 245);
    pdf.rect(0, 270, 210, 30, 'F');
    pdf.setFontSize(8);
    pdf.setTextColor(100, 100, 100);
    pdf.text('Ce document a été généré automatiquement par la plateforme Medical Appointment', 20, 284);
    pdf.text('En cas de questions, contactez-nous à support@medical-appointment.com', 20, 290);
    
    // Télécharger le PDF
    pdf.save(`justificatif-rdv-${referenceNumber}.pdf`);
  }

  // Méthode pour générer un PDF simple (pour les médecins)
  generateDoctorReceipt(appointmentData: any): void {
    const pdf = new jsPDF();
    
    pdf.setFontSize(18);
    pdf.text('JUSTIFICATIF MÉDECIN', 20, 30);
    
    pdf.setFontSize(12);
    pdf.text(`Patient: ${appointmentData.patient_name}`, 20, 50);
    pdf.text(`Date: ${appointmentData.appointment_date}`, 20, 65);
    pdf.text(`Heure: ${appointmentData.appointment_time}`, 20, 80);
    pdf.text(`Montant: ${appointmentData.amount} FCFA`, 20, 95);
    pdf.text(`Statut: ${appointmentData.status}`, 20, 110);
    
    pdf.save(`justificatif-medecin-${appointmentData.id}.pdf`);
  }
}