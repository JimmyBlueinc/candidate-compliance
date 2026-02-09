import { useState, useEffect, useMemo } from 'react';
import { 
  Calendar as CalendarIcon, 
  ChevronLeft, 
  ChevronRight, 
  FileText, 
  AlertCircle,
  CheckCircle,
  Clock,
  TrendingUp,
  XCircle,
  MapPin,
  User,
  Calendar
} from 'lucide-react';
import Layout from '../components/Layout/Layout';
import CredentialForm from '../components/CredentialForm';
import api from '../config/api';
import { useAuth } from '../contexts/AuthContext';

const CalendarView = () => {
  const { isAdmin } = useAuth();
  const [credentials, setCredentials] = useState([]);
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDate, setSelectedDate] = useState(null);
  const [loading, setLoading] = useState(true);
  const [viewMode, setViewMode] = useState('month'); // month, week
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCredential, setEditingCredential] = useState(null);

  const handleAdd = () => {
    setEditingCredential(null);
    setIsFormOpen(true);
  };

  const handleFormSuccess = async () => {
    await fetchCredentials();
  };

  useEffect(() => {
    fetchCredentials();
  }, []);

  const fetchCredentials = async () => {
    try {
      const response = await api.get('/credentials');
      setCredentials(response.data.data || response.data || []);
    } catch (err) {
      console.error('Error fetching credentials:', err);
    } finally {
      setLoading(false);
    }
  };

  const getDaysInMonth = (date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    const days = [];
    // Empty cells for days before month starts
    for (let i = 0; i < startingDayOfWeek; i++) {
      days.push(null);
    }
    // Days of the month
    for (let i = 1; i <= daysInMonth; i++) {
      days.push(new Date(year, month, i));
    }
    return days;
  };

  const getCredentialsForDate = (date) => {
    if (!date) return [];
    return credentials.filter(cred => {
      if (!cred.expiry_date) return false;
      const expiryDate = new Date(cred.expiry_date);
      return expiryDate.toDateString() === date.toDateString();
    });
  };

  const getStatusInfo = (cred) => {
    if (!cred.expiry_date) return { color: 'gray', label: 'Pending', icon: Clock };
    
    const expiryDate = new Date(cred.expiry_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    expiryDate.setHours(0, 0, 0, 0);
    const daysUntil = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));

    if (daysUntil < 0) {
      return { color: 'red', label: 'Expired', icon: XCircle, days: Math.abs(daysUntil) };
    } else if (daysUntil <= 30) {
      return { color: 'yellow', label: 'Expiring Soon', icon: AlertCircle, days: daysUntil };
    } else {
      return { color: 'green', label: 'Active', icon: CheckCircle, days: daysUntil };
    }
  };

  const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  const prevMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1));
    setSelectedDate(null);
  };

  const nextMonth = () => {
    setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1));
    setSelectedDate(null);
  };

  const goToToday = () => {
    const today = new Date();
    setCurrentDate(new Date(today.getFullYear(), today.getMonth(), 1));
    setSelectedDate(today);
  };

  const days = getDaysInMonth(currentDate);
  const selectedCredentials = selectedDate ? getCredentialsForDate(selectedDate) : [];

  // Calculate statistics
  const stats = useMemo(() => {
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    
    const thisMonth = credentials.filter(cred => {
      if (!cred.expiry_date) return false;
      const expiry = new Date(cred.expiry_date);
      expiry.setHours(0, 0, 0, 0);
      return expiry.getMonth() === currentDate.getMonth() && 
             expiry.getFullYear() === currentDate.getFullYear();
    });

    const expired = thisMonth.filter(cred => {
      const expiry = new Date(cred.expiry_date);
      expiry.setHours(0, 0, 0, 0);
      return expiry < now;
    });

    const expiringSoon = thisMonth.filter(cred => {
      const expiry = new Date(cred.expiry_date);
      expiry.setHours(0, 0, 0, 0);
      const daysUntil = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
      return daysUntil > 0 && daysUntil <= 30;
    });

    const active = thisMonth.filter(cred => {
      const expiry = new Date(cred.expiry_date);
      expiry.setHours(0, 0, 0, 0);
      const daysUntil = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
      return daysUntil > 30;
    });

    return {
      total: thisMonth.length,
      expired: expired.length,
      expiringSoon: expiringSoon.length,
      active: active.length,
    };
  }, [credentials, currentDate]);

  return (
    <Layout onAddClick={handleAdd}>
      <div className="space-y-6 animate-fade-in-up">
        {/* Header Section */}
        <div className="bg-gradient-to-r from-goodwill-primary via-goodwill-primary to-goodwill-primary/90 rounded-xl shadow-lg border border-goodwill-primary/20 p-6 text-white">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="h-12 w-12 rounded-xl bg-white/20 backdrop-blur-sm shadow-lg flex items-center justify-center border border-white/30">
                <CalendarIcon className="w-6 h-6 text-white" strokeWidth={2.5} />
              </div>
              <div>
                <h1 className="text-2xl font-bold tracking-tight">Calendar View</h1>
                <p className="text-white/80 text-sm mt-1">Track credential expiry dates at a glance</p>
              </div>
            </div>
            <button
              onClick={goToToday}
              className="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg border border-white/30 text-white font-medium text-sm transition-all duration-200 hover:scale-105 active:scale-95"
            >
              Today
            </button>
          </div>
        </div>

        {/* Statistics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="bg-white rounded-xl shadow-md border border-goodwill-border/50 p-5 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-goodwill-text-muted uppercase tracking-wider">Total This Month</p>
                <p className="text-3xl font-bold text-goodwill-dark mt-2">{stats.total}</p>
              </div>
              <div className="h-12 w-12 rounded-lg bg-goodwill-primary/10 flex items-center justify-center">
                <Calendar className="w-6 h-6 text-goodwill-primary" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-md border border-green-500/30 p-5 hover-lift" style={{ background: 'linear-gradient(135deg, rgba(34, 197, 94, 0.05) 0%, rgba(34, 197, 94, 0.02) 100%)' }}>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-goodwill-text-muted uppercase tracking-wider">Active</p>
                <p className="text-3xl font-bold text-green-600 mt-2">{stats.active}</p>
              </div>
              <div className="h-12 w-12 rounded-lg bg-green-500/10 flex items-center justify-center">
                <CheckCircle className="w-6 h-6 text-green-600" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-md border border-yellow-500/30 p-5 hover-lift" style={{ background: 'linear-gradient(135deg, rgba(234, 179, 8, 0.05) 0%, rgba(234, 179, 8, 0.02) 100%)' }}>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-goodwill-text-muted uppercase tracking-wider">Expiring Soon</p>
                <p className="text-3xl font-bold text-yellow-600 mt-2">{stats.expiringSoon}</p>
              </div>
              <div className="h-12 w-12 rounded-lg bg-yellow-500/10 flex items-center justify-center">
                <AlertCircle className="w-6 h-6 text-yellow-600" />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-md border border-red-500/30 p-5 hover-lift" style={{ background: 'linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(239, 68, 68, 0.02) 100%)' }}>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-goodwill-text-muted uppercase tracking-wider">Expired</p>
                <p className="text-3xl font-bold text-red-600 mt-2">{stats.expired}</p>
              </div>
              <div className="h-12 w-12 rounded-lg bg-red-500/10 flex items-center justify-center">
                <XCircle className="w-6 h-6 text-red-600" />
              </div>
            </div>
          </div>
        </div>

        {/* Calendar Section */}
        <div className="bg-white rounded-xl shadow-lg border border-goodwill-border/50 overflow-hidden">
          {/* Calendar Header */}
          <div className="bg-gradient-to-r from-goodwill-light to-white p-6 border-b border-goodwill-border/50">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <button
                  onClick={prevMonth}
                  className="h-10 w-10 rounded-lg bg-white hover:bg-goodwill-primary/10 border border-goodwill-border/50 flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-95 shadow-sm"
                >
                  <ChevronLeft className="w-5 h-5 text-goodwill-dark" />
                </button>
                <h2 className="text-2xl font-bold text-goodwill-dark min-w-[200px] text-center">
                  {monthNames[currentDate.getMonth()]} {currentDate.getFullYear()}
                </h2>
                <button
                  onClick={nextMonth}
                  className="h-10 w-10 rounded-lg bg-white hover:bg-goodwill-primary/10 border border-goodwill-border/50 flex items-center justify-center transition-all duration-200 hover:scale-110 active:scale-95 shadow-sm"
                >
                  <ChevronRight className="w-5 h-5 text-goodwill-dark" />
                </button>
              </div>
            </div>
          </div>

          {/* Calendar Grid */}
          <div className="p-6">
            {/* Day Headers */}
            <div className="grid grid-cols-7 gap-2 mb-3">
              {dayNames.map(day => (
                <div 
                  key={day} 
                  className="text-center text-xs font-bold text-goodwill-text-muted uppercase tracking-wider py-3"
                >
                  {day}
                </div>
              ))}
            </div>

            {/* Calendar Days */}
            <div className="grid grid-cols-7 gap-2">
              {days.map((day, index) => {
                const dayCredentials = day ? getCredentialsForDate(day) : [];
                const isToday = day && day.toDateString() === new Date().toDateString();
                const isSelected = day && selectedDate && day.toDateString() === selectedDate.toDateString();
                const isPast = day && day < new Date() && !isToday;

                return (
                  <div
                    key={index}
                    onClick={() => day && setSelectedDate(day)}
                    className={`min-h-[100px] p-2 border-2 rounded-xl cursor-pointer transition-all duration-200 relative overflow-hidden group ${
                      !day 
                        ? 'bg-transparent border-transparent cursor-default' 
                        : isSelected
                        ? 'bg-goodwill-primary/10 border-goodwill-primary shadow-lg scale-105 z-10'
                        : isToday
                        ? 'bg-gradient-to-br from-goodwill-primary/5 to-goodwill-primary/10 border-goodwill-primary/50 shadow-md hover:shadow-lg'
                        : isPast
                        ? 'bg-gray-50/50 border-gray-200 hover:border-gray-300 hover:bg-gray-100/50'
                        : 'bg-white border-goodwill-border/50 hover:border-goodwill-primary/50 hover:bg-goodwill-light/50 hover:shadow-md'
                    }`}
                  >
                    {day && (
                      <>
                        <div className={`text-sm font-bold mb-2 flex items-center justify-between ${
                          isToday 
                            ? 'text-goodwill-primary' 
                            : isSelected
                            ? 'text-goodwill-primary'
                            : isPast
                            ? 'text-gray-400'
                            : 'text-goodwill-dark'
                        }`}>
                          <span>{day.getDate()}</span>
                          {isToday && (
                            <span className="h-1.5 w-1.5 rounded-full bg-goodwill-primary"></span>
                          )}
                        </div>
                        
                        <div className="space-y-1">
                          {dayCredentials.slice(0, 2).map((cred, idx) => {
                            const statusInfo = getStatusInfo(cred);
                            const StatusIcon = statusInfo.icon;
                            return (
                              <div
                                key={idx}
                                className={`text-[10px] px-1.5 py-0.5 rounded-md font-medium truncate flex items-center gap-1 ${
                                  statusInfo.color === 'red'
                                    ? 'bg-red-100 text-red-700 border border-red-200'
                                    : statusInfo.color === 'yellow'
                                    ? 'bg-yellow-100 text-yellow-700 border border-yellow-200'
                                    : 'bg-green-100 text-green-700 border border-green-200'
                                }`}
                                title={`${cred.candidate_name} - ${cred.credential_type}`}
                              >
                                <StatusIcon className="w-2.5 h-2.5 flex-shrink-0" />
                                <span className="truncate">{cred.candidate_name}</span>
                              </div>
                            );
                          })}
                          {dayCredentials.length > 2 && (
                            <div className="text-[10px] text-goodwill-text-muted font-medium px-1.5 py-0.5 bg-goodwill-light rounded-md border border-goodwill-border/50">
                              +{dayCredentials.length - 2} more
                            </div>
                          )}
                        </div>
                      </>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        {/* Selected Date Credentials */}
        {selectedDate && (
          <div className="bg-white rounded-xl shadow-lg border border-goodwill-border/50 overflow-hidden animate-fade-in-up">
            <div className="bg-gradient-to-r from-goodwill-primary/10 to-goodwill-primary/5 p-5 border-b border-goodwill-border/50">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="h-10 w-10 rounded-lg bg-goodwill-primary/20 flex items-center justify-center">
                    <Calendar className="w-5 h-5 text-goodwill-primary" />
                  </div>
                  <div>
                    <h3 className="text-lg font-bold text-goodwill-dark">
                      {selectedDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                    </h3>
                    <p className="text-xs text-goodwill-text-muted mt-0.5">
                      {selectedCredentials.length} {selectedCredentials.length === 1 ? 'credential' : 'credentials'} expiring
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="p-6">
              {selectedCredentials.length === 0 ? (
                <div className="text-center py-12">
                  <div className="h-16 w-16 rounded-full bg-goodwill-light mx-auto flex items-center justify-center mb-4">
                    <CheckCircle className="w-8 h-8 text-goodwill-text-muted" />
                  </div>
                  <p className="text-sm font-medium text-goodwill-text-muted">No credentials expiring on this date</p>
                  <p className="text-xs text-goodwill-text-muted mt-1">All clear! 🎉</p>
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {selectedCredentials.map(cred => {
                    const statusInfo = getStatusInfo(cred);
                    const StatusIcon = statusInfo.icon;
                    return (
                      <div
                        key={cred.id}
                        className={`p-4 rounded-xl border-2 transition-all duration-200 hover-lift ${
                          statusInfo.color === 'red'
                            ? 'bg-red-50/50 border-red-200'
                            : statusInfo.color === 'yellow'
                            ? 'bg-yellow-50/50 border-yellow-200'
                            : 'bg-green-50/50 border-green-200'
                        }`}
                      >
                        <div className="flex items-start gap-3">
                          <div className={`h-10 w-10 rounded-lg flex items-center justify-center flex-shrink-0 ${
                            statusInfo.color === 'red'
                              ? 'bg-red-100'
                              : statusInfo.color === 'yellow'
                              ? 'bg-yellow-100'
                              : 'bg-green-100'
                          }`}>
                            <StatusIcon className={`w-5 h-5 ${
                              statusInfo.color === 'red'
                                ? 'text-red-600'
                                : statusInfo.color === 'yellow'
                                ? 'text-yellow-600'
                                : 'text-green-600'
                            }`} />
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center justify-between mb-1">
                              <p className="text-sm font-bold text-goodwill-dark truncate">{cred.candidate_name}</p>
                              <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${
                                statusInfo.color === 'red'
                                  ? 'bg-red-100 text-red-700'
                                  : statusInfo.color === 'yellow'
                                  ? 'bg-yellow-100 text-yellow-700'
                                  : 'bg-green-100 text-green-700'
                              }`}>
                                {statusInfo.label}
                              </span>
                            </div>
                            <p className="text-xs text-goodwill-text-muted mb-2">{cred.credential_type}</p>
                            <div className="flex items-center gap-4 text-xs">
                              <div className="flex items-center gap-1.5 text-goodwill-text-muted">
                                <User className="w-3.5 h-3.5" />
                                <span>{cred.recruiter_name || 'N/A'}</span>
                              </div>
                              {statusInfo.days !== undefined && (
                                <div className={`flex items-center gap-1.5 font-medium ${
                                  statusInfo.color === 'red'
                                    ? 'text-red-600'
                                    : statusInfo.color === 'yellow'
                                    ? 'text-yellow-600'
                                    : 'text-green-600'
                                }`}>
                                  <Clock className="w-3.5 h-3.5" />
                                  <span>
                                    {statusInfo.days === 0 
                                      ? 'Expires today' 
                                      : statusInfo.days < 0 
                                      ? `${Math.abs(statusInfo.days)} days ago`
                                      : `${statusInfo.days} days left`
                                    }
                                  </span>
                                </div>
                              )}
                            </div>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Credential Form */}
      <CredentialForm
        isOpen={isFormOpen}
        onClose={() => setIsFormOpen(false)}
        onSuccess={handleFormSuccess}
        credential={editingCredential}
      />
    </Layout>
  );
};

export default CalendarView;
