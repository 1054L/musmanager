const API_URL = 'http://localhost:8002/api';

export const authService = {
  async login(email, password) {
    const response = await fetch(`${API_URL}/me`, {
      headers: {
        'Authorization': `Basic ${btoa(`${email}:${password}`)}`
      }
    });

    if (!response.ok) {
      throw new Error('Email o contraseña incorrectos');
    }

    const user = await response.json();
    const authData = { 
      email, 
      password, // Storing for Basic Auth simulation
      roles: user.roles,
      id: user.id
    };
    
    localStorage.setItem('user', JSON.stringify(authData));
    return authData;
  },

  async register(email, password) {
    const response = await fetch(`${API_URL}/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.error || 'Error en el registro');
    }

    return response.json();
  },

  logout() {
    localStorage.removeItem('user');
  },

  getUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },

  getAuthHeader() {
    const user = this.getUser();
    if (user && user.email && user.password) {
      return { 'Authorization': `Basic ${btoa(`${user.email}:${user.password}`)}` };
    }
    return {};
  }
};

export const tournamentService = {
  async getTournament(uuid) {
    const response = await fetch(`${API_URL}/tournament/${uuid}`, {
      headers: authService.getAuthHeader()
    });
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.error || `Error ${response.status}: Torneo no encontrado`);
    }
    return response.json();
  },

  async getManagedTournaments() {
    const response = await fetch(`${API_URL}/user/tournaments`, {
      headers: authService.getAuthHeader()
    });
    if (!response.ok) {
      throw new Error('Error al cargar torneos');
    }
    return response.json();
  },

  async getPublicTournaments() {
    const response = await fetch(`${API_URL}/public/tournaments`);
    if (!response.ok) {
      throw new Error('Error al cargar la lista pública de torneos');
    }
    return response.json();
  },

  async createTournament(data) {
    const isFormData = data instanceof FormData;
    const response = await fetch(`${API_URL}/admin/tournament`, {
      method: 'POST',
      headers: {
        ...authService.getAuthHeader(),
        ...(isFormData ? {} : { 'Content-Type': 'application/json' })
      },
      body: isFormData ? data : JSON.stringify(data)
    });

    if (!response.ok) {
      let errorMessage = 'Error al crear el torneo';
      try {
        const errorData = await response.json();
        errorMessage = errorData.error || errorData.detail || errorData.title || errorMessage;
      } catch (e) {
        // Fallback for HTML error pages or empty bodies
        if (response.status === 403) errorMessage = 'No tienes permisos para realizar esta acción';
        else if (response.status === 401) errorMessage = 'Sesión expirada o inválida';
        else errorMessage = `Error del servidor (${response.status})`;
      }
      throw new Error(errorMessage);
    }
    return response.json();
  },

  async updateTournament(uuid, data) {
    const isFormData = data instanceof FormData;
    // Using POST for update to better support file uploads in various environments
    const response = await fetch(`${API_URL}/admin/tournament/${uuid}`, {
      method: 'POST', 
      headers: {
        ...authService.getAuthHeader(),
        ...(isFormData ? {} : { 'Content-Type': 'application/json' })
      },
      body: isFormData ? data : JSON.stringify(data)
    });

    if (!response.ok) {
      let errorMessage = 'Error al actualizar el torneo';
      try {
        const errorData = await response.json();
        errorMessage = errorData.error || errorData.detail || errorData.title || errorMessage;
      } catch (e) {
        if (response.status === 403) errorMessage = 'No tienes permisos para realizar esta acción';
        else if (response.status === 401) errorMessage = 'Sesión expirada o inválida';
        else errorMessage = `Error del servidor (${response.status})`;
      }
      throw new Error(errorMessage);
    }
    return response.json();
  },

  async enrollTeam(uuid, teamId) {
    const formData = new FormData();
    formData.append('teamId', teamId);
    const response = await fetch(`${API_URL}/admin/tournament/${uuid}/enroll-team`, {
      method: 'POST',
      headers: authService.getAuthHeader(),
      body: formData
    });
    if (!response.ok) {
      let errorMessage = 'Error al inscribir equipo';
      try {
        const errorData = await response.json();
        errorMessage = errorData.error || errorData.detail || errorData.title || errorMessage;
      } catch (e) {
        if (response.status === 403) errorMessage = 'No tienes permisos para realizar esta acción';
        else if (response.status === 401) errorMessage = 'Sesión expirada o inválida';
        else errorMessage = `Error del servidor (${response.status})`;
      }
      throw new Error(errorMessage);
    }
    return response.json();
  },

  async generateGroups(uuid, groupsCount) {
    const formData = new FormData();
    formData.append('groupsCount', groupsCount);
    const response = await fetch(`${API_URL}/admin/tournament/${uuid}/generate-groups`, {
      method: 'POST',
      headers: authService.getAuthHeader(),
      body: formData
    });
    if (!response.ok) {
      let errorMessage = 'Error al generar grupos';
      try {
        const errorData = await response.json();
        errorMessage = errorData.error || errorData.detail || errorData.title || errorMessage;
      } catch (e) {
        if (response.status === 403) errorMessage = 'No tienes permisos para realizar esta acción';
        else if (response.status === 401) errorMessage = 'Sesión expirada o inválida';
        else errorMessage = `Error del servidor (${response.status})`;
      }
      throw new Error(errorMessage);
    }
    return response.json();
  },

  async generateMatches(uuid) {
    const response = await fetch(`${API_URL}/admin/tournament/${uuid}/generate-matches`, {
      method: 'POST',
      headers: authService.getAuthHeader()
    });
    if (!response.ok) {
      let errorMessage = 'Error al generar partidos';
      try {
        const errorData = await response.json();
        errorMessage = errorData.error || errorData.detail || errorData.title || errorMessage;
      } catch (e) {
        if (response.status === 403) errorMessage = 'No tienes permisos para realizar esta acción';
        else if (response.status === 401) errorMessage = 'Sesión expirada o inválida';
        else errorMessage = `Error del servidor (${response.status})`;
      }
      throw new Error(errorMessage);
    }
    return response.json();
  },

  async updateMatchScore(matchId, score1, score2) {
    const formData = new FormData();
    formData.append('score1', score1);
    formData.append('score2', score2);
    const response = await fetch(`${API_URL}/admin/match/${matchId}`, {
      method: 'POST',
      headers: authService.getAuthHeader(),
      body: formData
    });
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.error || 'Error al actualizar resultado');
    }
    return response.json();
  },

  async getClassification(uuid) {
    const response = await fetch(`${API_URL}/tournament/${uuid}/classification`);
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.error || 'Error al cargar clasificación');
    }
    return response.json();
  }
};

export const playerService = {
  async getPlayers() {
    const response = await fetch(`${API_URL}/players`, {
      headers: authService.getAuthHeader()
    });
    if (!response.ok) throw new Error('Error al cargar jugadores');
    return response.json();
  },
  async createPlayer(data) {
    const response = await fetch(`${API_URL}/admin/player`, {
      method: 'POST',
      headers: { ...authService.getAuthHeader(), 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.error || 'Error al crear jugador');
    }
    return response.json();
  }
};

export const teamService = {
  async getTeams() {
    const response = await fetch(`${API_URL}/teams`, {
      headers: authService.getAuthHeader()
    });
    if (!response.ok) throw new Error('Error al cargar equipos');
    return response.json();
  },
  async createTeam(data) {
    const response = await fetch(`${API_URL}/admin/team`, {
      method: 'POST',
      headers: { ...authService.getAuthHeader(), 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.error || 'Error al crear equipo');
    }
    return response.json();
  }
};
