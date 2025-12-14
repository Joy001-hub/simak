/**
 * Dummy Data Service untuk aplikasi Kavling Management Pro
 * Menyediakan akses ke data dummy dari public/assets/data-dummy.json
 */

export async function loadDummyData() {
  try {
    const response = await fetch('/assets/data-dummy.json');
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    console.log('Dummy data loaded successfully:', data);
    return data;
  } catch (error) {
    console.error('Failed to load dummy data:', error);
    return null;
  }
}

export async function getProjects() {
  const data = await loadDummyData();
  return data?.projects || [];
}

export async function getLots() {
  const data = await loadDummyData();
  return data?.lots || [];
}

export async function getLotsByProject(projectId) {
  const lots = await getLots();
  return lots.filter(lot => lot.project_id === projectId);
}

export async function getProjectById(projectId) {
  const projects = await getProjects();
  return projects.find(p => p.id === projectId);
}

// Initialize on load
document.addEventListener('DOMContentLoaded', async () => {
  console.log('Initializing dummy data service...');
  const data = await loadDummyData();
  if (data) {
    window.dummyData = data;
    console.log('Dummy data is now available at window.dummyData');
  }
});
