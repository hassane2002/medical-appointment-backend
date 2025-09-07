import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AppointmentsHistory } from './appointments-history';

describe('AppointmentsHistory', () => {
  let component: AppointmentsHistory;
  let fixture: ComponentFixture<AppointmentsHistory>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AppointmentsHistory]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AppointmentsHistory);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
